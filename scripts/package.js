/**
 * 主题打包脚本
 *
 * 产出可直接解压到 usr/themes/ 的 zip，顶层为 bubble/。
 *
 * 文件清单用「白名单」而不是排除法：排除法要求穷举 node_modules / src /
 * docs / dist.bak 等一切不该发布的路径，漏掉任何一项都会把源码或审查报告
 * 发出去；白名单只需回答「Typecho 运行时需要什么」，答案短且不随工具链变化。
 * docs/ 与 scripts/ 因此天然不进包。
 *
 * 复制到 stage 后会剥离全部注释，发布包与仓库源码的差异仅此一项。
 *
 * 用法：npm run package
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { zipDirectory } = require('./zip');

const ROOT = path.join(__dirname, '..');
const OUT_DIR = path.join(ROOT, '.package');
const STAGE = path.join(OUT_DIR, 'stage');

/** Typecho 运行时会读到的文件 / 目录，其它一律不进包 */
const INCLUDE = [
  'index.php',            // 主题入口 + 主题头信息
  'post.php',
  'archive.php',
  'page.php',
  'page_recent_modified.php',
  'functions.php',        // Typecho 的主题钩子入口
  'core',                 // 配置 / 公共函数 / 自定义表单元素
  'views',                // 视图片段，由 need() 包含
  'dist',                 // 构建产物，必须先 npm run build
  'assets',               // 不经构建、由 PHP 直接引用的图片
  'screenshot.png',
  'README.md',
  'LICENSE',
];

function fail(msg) {
  console.error(`[package] ${msg}`);
  process.exit(1);
}

function copy(src, dest) {
  const stat = fs.statSync(src);
  if (stat.isDirectory()) {
    fs.mkdirSync(dest, { recursive: true });
    for (const name of fs.readdirSync(src)) copy(path.join(src, name), path.join(dest, name));
  } else {
    fs.copyFileSync(src, dest);
  }
}

function walk(dir, onFile) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p, onFile);
    else onFile(p);
  }
}

/** 探测可用的 PHP CLI，用于注释剥离与语法校验 */
function detectPhp() {
  for (const bin of [process.env.PHP_BINARY, 'php'].filter(Boolean)) {
    try {
      execFileSync(bin, ['-r', 'echo 1;'], { stdio: 'pipe' });
      return bin;
    } catch (e) {
      // 换下一个候选
    }
  }
  return null;
}

/**
 * 剥离发布包里的全部注释
 *
 * 注释对下载主题的用户没有价值：行内 HTML 注释会真正传输到浏览器，
 * 其余则暴露实现细节并占用体积。
 *
 * dist/ 下的 CSS / JS 先经 webpack 压缩器处理，非注释内容已清空，
 * 仅存的许可证横幅属 MIT 授权要求，予以保留。
 */
function stripComments(themeDir) {
  const php = detectPhp();
  if (!php) fail('未找到 PHP CLI，无法剥离注释；可用环境变量 PHP_BINARY 指定 php 路径');

  // index.php 的首个文档注释是 Typecho 的主题元信息，必须保留
  execFileSync(php, [path.join(__dirname, 'strip-comments.php'), themeDir, '--keep-index-doc'], {
    stdio: 'inherit',
  });

  const phpFiles = [];
  walk(themeDir, (p) => {
    if (p.endsWith('.php')) phpFiles.push(p);
  });

  // 语法校验：剥离若破坏代码，必须挡在这里，而不是等用户装上后白屏
  for (const file of phpFiles) {
    try {
      execFileSync(php, ['-l', file], { stdio: 'pipe' });
    } catch (e) {
      fail(`注释剥离破坏了语法: ${path.relative(ROOT, file)}\n${e.stderr || e.message}`);
    }
  }

  console.log(`[package] 已剥离注释，${phpFiles.length} 个 PHP 文件语法校验通过`);
}

function main() {
  const pkg = JSON.parse(fs.readFileSync(path.join(ROOT, 'package.json'), 'utf8'));
  const version = String(pkg.version || '');
  const themeName = String(pkg.name || 'theme');
  if (!version) fail('package.json 里没有 version');

  // 构建产物是运行时依赖，缺失说明忘了 npm run build
  if (!fs.existsSync(path.join(ROOT, 'dist', 'main.min.css'))) {
    fail('dist/main.min.css 不存在，请先运行命令 npm run build');
  }

  const missing = INCLUDE.filter((n) => !fs.existsSync(path.join(ROOT, n)));
  if (missing.length) fail(`以下条目不存在，打包清单需要更新：${missing.join(', ')}`);

  fs.rmSync(OUT_DIR, { recursive: true, force: true });

  // 先复制到 stage/<主题名>/，保证 zip 解开后是 usr/themes/bubble/ 而不是散落一地
  const themeDir = path.join(STAGE, themeName);
  fs.mkdirSync(themeDir, { recursive: true });
  for (const name of INCLUDE) copy(path.join(ROOT, name), path.join(themeDir, name));

  const measure = () => {
    let files = 0;
    let bytes = 0;
    walk(themeDir, (p) => {
      files += 1;
      bytes += fs.statSync(p).size;
    });
    return { files, bytes };
  };

  const before = measure();
  stripComments(themeDir);
  const after = measure();

  const zipPath = path.join(OUT_DIR, `${themeName}-${version}.zip`);

  // 压缩对象是 stage/<主题名>/：这样 zip 里的顶层目录就是 bubble/，
  // 用户解压到 usr/themes/ 正好落成 usr/themes/bubble/。
  // 打包清单已在复制阶段生效，这里不需要再逐项过滤。
  zipDirectory(STAGE, zipPath);

  fs.rmSync(STAGE, { recursive: true, force: true });

  const zipSize = fs.statSync(zipPath).size;
  const saved = ((before.bytes - after.bytes) / 1024).toFixed(1);
  console.log(`[package] ${path.relative(ROOT, zipPath)}`);
  console.log(`[package] 主题 ${themeName} ${version}｜${after.files} 个文件｜` +
    `源 ${(before.bytes / 1024).toFixed(1)} KiB → 去注释后 ${(after.bytes / 1024).toFixed(1)} KiB` +
    `（省 ${saved} KiB）｜压缩包 ${(zipSize / 1024).toFixed(1)} KiB`);
  console.log('[package] 解压到 usr/themes/ 即可覆盖安装');
}

main();
