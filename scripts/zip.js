/**
 * 最小 ZIP 打包器，只服务本主题的发布包（deflate 压缩，不做 ZIP64）
 *
 * 为什么自己写而不用系统工具：
 *   Windows 上 Compress-Archive 与 .NET Framework 的 ZipFile.CreateFromDirectory
 *   都会把条目名的分隔符写成反斜杠，而 zip 规范只认正斜杠。此类压缩包在
 *   macOS / Linux 解压会得到一堆名为 bubble\views\include.php 的文件，
 *   在 Windows 上解压则一切正常，问题只在跨平台时才暴露。
 *   自己写可保证各平台输出一致，且不依赖外部命令（CI 上免装 zip）。
 *
 * 不支持 ZIP64：需要 >4 GiB 或 >65535 个条目。发布包远小于该量级，
 * 真到那天会在写入前因 writeUInt32LE 越界而报错，不会静默产出坏包。
 */
const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

const CRC_TABLE = (() => {
  const table = new Int32Array(256);
  for (let n = 0; n < 256; n += 1) {
    let c = n;
    for (let k = 0; k < 8; k += 1) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    table[n] = c;
  }
  return table;
})();

function crc32(buf) {
  let c = -1;
  for (let i = 0; i < buf.length; i += 1) c = CRC_TABLE[(c ^ buf[i]) & 0xff] ^ (c >>> 8);
  return (c ^ -1) >>> 0;
}

/** 打包只记录到秒的 DOS 时间戳 */
function dosDateTime(date) {
  return {
    time: ((date.getHours() << 11) | (date.getMinutes() << 5) | (date.getSeconds() >> 1)) & 0xffff,
    day: (((date.getFullYear() - 1980) << 9) | ((date.getMonth() + 1) << 5) | date.getDate()) & 0xffff,
  };
}

/** 递归收集文件，条目名一律拼成正斜杠（不写入目录条目，解压端会按需建目录） */
function collect(dir, prefix = '') {
  const out = [];
  const entries = fs.readdirSync(dir, { withFileTypes: true })
    .sort((a, b) => (a.name < b.name ? -1 : 1));

  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    const name = prefix ? `${prefix}/${entry.name}` : entry.name;
    if (entry.isDirectory()) out.push(...collect(full, name));
    else out.push({ name, data: fs.readFileSync(full), mtime: fs.statSync(full).mtime });
  }
  return out;
}

/** 把 dir 的内容打进 zipPath，条目名相对 dir */
function zipDirectory(dir, zipPath) {
  const files = collect(dir);
  const localParts = [];
  const centralParts = [];
  let localSize = 0;

  for (const file of files) {
    const nameBuf = Buffer.from(file.name, 'utf8');
    const deflated = zlib.deflateRawSync(file.data, { level: 9 });
    // 小文件或已压缩内容压不动时直接存原文，省下 deflate 头开销
    const useDeflate = deflated.length < file.data.length;
    const payload = useDeflate ? deflated : file.data;
    const method = useDeflate ? 8 : 0;
    const crc = crc32(file.data);
    const { time, day } = dosDateTime(file.mtime);

    const local = Buffer.alloc(30);
    local.writeUInt32LE(0x04034b50, 0);
    local.writeUInt16LE(20, 4);
    local.writeUInt16LE(0x0800, 6);          // 文件名按 UTF-8 解释
    local.writeUInt16LE(method, 8);
    local.writeUInt16LE(time, 10);
    local.writeUInt16LE(day, 12);
    local.writeUInt32LE(crc, 14);
    local.writeUInt32LE(payload.length, 18);
    local.writeUInt32LE(file.data.length, 22);
    local.writeUInt16LE(nameBuf.length, 26);
    local.writeUInt16LE(0, 28);
    localParts.push(local, nameBuf, payload);

    const central = Buffer.alloc(46);
    central.writeUInt32LE(0x02014b50, 0);
    central.writeUInt16LE(0x031e, 4);        // made by Unix 3.0，声明用正斜杠
    central.writeUInt16LE(20, 6);
    central.writeUInt16LE(0x0800, 8);
    central.writeUInt16LE(method, 10);
    central.writeUInt16LE(time, 12);
    central.writeUInt16LE(day, 14);
    central.writeUInt32LE(crc, 16);
    central.writeUInt32LE(payload.length, 20);
    central.writeUInt32LE(file.data.length, 24);
    central.writeUInt16LE(nameBuf.length, 28);
    central.writeUInt16LE(0, 30);
    central.writeUInt16LE(0, 32);
    central.writeUInt16LE(0, 34);
    central.writeUInt16LE(0, 36);
    central.writeUInt32LE(0o644 << 16, 38);  // 权限 644
    central.writeUInt32LE(localSize, 42);
    centralParts.push(central, nameBuf);

    localSize += local.length + nameBuf.length + payload.length;
  }

  const centralBuf = Buffer.concat(centralParts);
  const eocd = Buffer.alloc(22);
  eocd.writeUInt32LE(0x06054b50, 0);
  eocd.writeUInt16LE(0, 4);
  eocd.writeUInt16LE(0, 6);
  eocd.writeUInt16LE(files.length, 8);
  eocd.writeUInt16LE(files.length, 10);
  eocd.writeUInt32LE(centralBuf.length, 12);
  eocd.writeUInt32LE(localSize, 16);
  eocd.writeUInt16LE(0, 20);

  fs.writeFileSync(zipPath, Buffer.concat([...localParts, centralBuf, eocd]));
}

module.exports = { zipDirectory };
