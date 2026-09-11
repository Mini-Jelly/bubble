/**
 * 主题打包脚本
 *
 * 产出一个可以直接丢进 `usr/themes/` 解压的 zip（顶层带 bubble/ 目录）。
 *
 * 为什么用「白名单」而不是排除法：
 *   排除法必须先穷举 node_modules / src / webpack / scripts / docs / .audit-tmp /
 *   dist.bak / 各种配置文件……漏掉任何一项，源码、审查报告甚至别人的构建缓存就会
 *   跟着发布出去。白名单只需要回答一个问题：「Typecho 运行时到底要哪些文件」，
 *   答案短、稳定、不会随工具链变化。
 *
 * 为什么要把「文档不进发布包」从 .gitignore 搬到本脚本：
 *   审查报告（docs/AUDIT.md）对维护者有价值、对下载主题的用户没价值。
 *   .gitignore 只能表达「不进仓库」，表达不了「进仓库但不进发布包」——
 *   放错地方的结果是仓库里一份设计文档都不剩。交给打包脚本排除才准确。
 *
 * 用法：npm run package
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

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
  'dist',                 // 构建产物：CSS / JS / 字体（必须先 npm run build）
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

function main() {
  const pkg = JSON.parse(fs.readFileSync(path.join(ROOT, 'package.json'), 'utf8'));
  const version = String(pkg.version || '');
  const themeName = String(pkg.name || 'theme');
  if (!version) fail('package.json 里没有 version');

  // 构建产物是运行时依赖，缺失说明忘了 npm run build
  if (!fs.existsSync(path.join(ROOT, 'dist', 'main.min.css'))) {
    fail('dist/main.min.css 不存在，请先跑 npm run build');
  }

  const missing = INCLUDE.filter((n) => !fs.existsSync(path.join(ROOT, n)));
  if (missing.length) fail(`以下条目不存在，打包清单需要更新：${missing.join(', ')}`);

  fs.rmSync(OUT_DIR, { recursive: true, force: true });

  // 先复制到 stage/<主题名>/，保证 zip 解开后是 usr/themes/bubble/ 而不是散落一地
  const themeDir = path.join(STAGE, themeName);
  fs.mkdirSync(themeDir, { recursive: true });
  for (const name of INCLUDE) copy(path.join(ROOT, name), path.join(themeDir, name));

  let files = 0;
  let bytes = 0;
  const walk = (dir) => {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
      const p = path.join(dir, e.name);
      if (e.isDirectory()) walk(p);
      else { files += 1; bytes += fs.statSync(p).size; }
    }
  };
  walk(themeDir);

  const zipPath = path.join(OUT_DIR, `${themeName}-${version}.zip`);

  // 压缩对象是整个 stage/<主题名>/：这样 zip 里的顶层目录就是 bubble/，
  // 用户解压到 usr/themes/ 正好落成 usr/themes/bubble/。
  // 打包清单已在复制阶段生效，这里不需要再逐项过滤。
  if (process.platform === 'win32') {
    const args = ['-NoProfile', '-NonInteractive', '-Command',
      `Compress-Archive -Force -LiteralPath '${themeDir.replace(/'/g, "''")}'` +
      ` -DestinationPath '${zipPath.replace(/'/g, "''")}'`];
    execFileSync('powershell.exe', args, { stdio: 'inherit' });
  } else {
    execFileSync('zip', ['-rq', zipPath, themeName], { cwd: STAGE, stdio: 'inherit' });
  }

  fs.rmSync(STAGE, { recursive: true, force: true });

  const zipSize = fs.statSync(zipPath).size;
  console.log(`[package] ${path.relative(ROOT, zipPath)}`);
  console.log(`[package] 主题 ${themeName} ${version}｜${files} 个文件｜` +
    `源 ${(bytes / 1024).toFixed(1)} KiB → 压缩后 ${(zipSize / 1024).toFixed(1)} KiB`);
  console.log('[package] 解压到 usr/themes/ 即可覆盖安装');
}

main();
