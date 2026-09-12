<?php
/**
 * 发布包注释剥离器，只由 scripts/package.js 在打包阶段调用
 *
 * 作用对象是 stage 目录里的副本，仓库源码的注释不受影响。
 *
 * 用法：php scripts/strip-comments.php <文件或目录> [--keep-index-doc]
 *
 * 为什么用 token_get_all() 而不是正则：
 *   正则分不清「注释」与「字符串里的注释符号」——"http://x" 的双斜杠、
 *   heredoc 正文、'<!--more-->' 这类字面量都会被误切。token_get_all()
 *   是 PHP 自己的词法器，边界判断天然正确。
 *
 * --keep-index-doc 保留 index.php 的第一个文档注释：
 *   Typecho 的 Plugin::parseInfo() 靠它读取主题名 / 作者 / 版本，
 *   core/func.php 的 getThemeVersion() 也从中取 @version。剥掉它，
 *   后台主题列表的元信息会全部变空。
 *
 * 删除注释后保留其占用的换行，使产物行号与源码一致，便于对照报错位置。
 */

$targets = [];
$keepIndexDoc = false;

foreach (array_slice($argv, 1) as $arg) {
  if ($arg === '--keep-index-doc') {
    $keepIndexDoc = true;
  } else {
    $targets[] = $arg;
  }
}

if (!$targets) {
  fwrite(STDERR, "用法: php strip-comments.php <文件或目录> [--keep-index-doc]\n");
  exit(1);
}

/** 只保留换行符，用于在删除注释后维持原有行号 */
function keepNewlines(string $text): string
{
  return (string) preg_replace('/[^\r\n]+/', '', $text);
}

function stripFile(string $path, bool $keepFirstDoc): void
{
  $docKept = false;
  $out = '';

  foreach (token_get_all((string) file_get_contents($path)) as $token) {
    // 单字符 token（; { } 等）以字符串形式返回
    if (is_string($token)) {
      $out .= $token;
      continue;
    }

    [$id, $text] = $token;

    if ($id === T_COMMENT) {
      $out .= keepNewlines($text);
    } elseif ($id === T_DOC_COMMENT) {
      if ($keepFirstDoc && !$docKept) {
        $docKept = true;
        $out .= $text;
      } else {
        $out .= keepNewlines($text);
      }
    } elseif ($id === T_INLINE_HTML) {
      // 行内 HTML 的注释会真正传输到浏览器；<!--[if ...]> 条件注释保留
      $out .= (string) preg_replace_callback(
        '/<!--(?!\[if)[\s\S]*?-->/i',
        static function (array $match): string {
          return keepNewlines($match[0]);
        },
        $text
      );
    } else {
      $out .= $text;
    }
  }

  file_put_contents($path, $out);
}

$files = [];

foreach ($targets as $target) {
  if (is_file($target)) {
    $files[] = $target;
    continue;
  }

  if (!is_dir($target)) {
    fwrite(STDERR, "跳过不存在的路径: $target\n");
    continue;
  }

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
      $files[] = $file->getPathname();
    }
  }
}

foreach ($files as $file) {
  stripFile($file, $keepIndexDoc && basename($file) === 'index.php');
}

echo '[strip] 已剥离 ' . count($files) . " 个文件的注释\n";
