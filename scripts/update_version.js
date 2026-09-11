/**
 * 版本号同步脚本
 *
 * 单一真源：package.json 的 version（SemVer）。
 * 本脚本把它同步到 index.php 的主题头注释，并按当天日期刷新 @build。
 *
 * 旧实现是把「Beat + 当天日期」当作版本号写进 index.php，等于每次构建都
 * 换一个版本号，历史版本之间无法比较，package.json 的 version 也长期停在
 * 1.0.0 没动过。现在版本号与构建日期分开：
 *   @version 语义化版本，只在发版时递增，与 git tag vX.Y.Z 一一对应
 *   @build   构建日期 YYYYMMDD，每次同步自动刷新，用于排查线上是哪一版
 *
 * 用法：
 *   npm run version:sync     只同步（不改版本号）
 *   npm run release:patch    升补丁号 + 同步 + 提交 + 打 tag
 *   npm run release:minor    升次版本号，同上
 *   npm run release:major    升主版本号，同上
 */
const fs = require('fs');
const path = require('path');

const PKG_PATH = path.join(__dirname, '..', 'package.json');
const THEME_ENTRY = path.join(__dirname, '..', 'index.php');

/** 取当天日期，格式 YYYYMMDD */
function today() {
  const now = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
}

function main() {
  const version = String(JSON.parse(fs.readFileSync(PKG_PATH, 'utf8')).version || '');

  if (!/^\d+\.\d+\.\d+$/.test(version)) {
    console.error(`[version] package.json 的 version 不是合法 SemVer: "${version}"`);
    process.exit(1);
  }

  if (!fs.existsSync(THEME_ENTRY)) {
    console.error(`[version] 找不到主题入口文件: ${THEME_ENTRY}`);
    process.exit(1);
  }

  const build = today();
  const source = fs.readFileSync(THEME_ENTRY, 'utf8');

  let next = source
    // 主题头第一行： * bubble 1.1.0
    .replace(/^(\s*\*\s*)bubble[^\r\n]*$/m, (_, lead) => `${lead}bubble ${version}`)
    //  * @version 1.1.0
    .replace(/^(\s*\*\s*@version\s+)[^\r\n]*$/m, (_, lead) => `${lead}${version}`);

  if (/^\s*\*\s*@build\s+/m.test(next)) {
    // 已有 @build 行：刷新日期
    next = next.replace(/^(\s*\*\s*@build\s+)[^\r\n]*$/m, (_, lead) => `${lead}${build}`);
  } else {
    // 老主题头没有 @build：在 @version 后面补一行
    next = next.replace(
      /^(\s*\*\s*@version\s+[^\r\n]*)$/m,
      (line) => `${line}\n * @build ${build}`
    );
  }

  if (next === source) {
    console.log(`[version] index.php 已是 ${version}（build ${build}），无需改动`);
    return;
  }

  fs.writeFileSync(THEME_ENTRY, next, 'utf8');
  console.log(`[version] index.php 已同步为 ${version}（build ${build}）`);
}

main();
