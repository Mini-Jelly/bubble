<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="<?php $this->options->charset(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta property="og:url" content="<?php $this->permalink(); ?>">
<meta property="og:site_name" content="<?php $this->options->title(); ?>">
<meta property="og:description" content="<?php $this->options->description(); ?>">
<meta name="keywords" content="<?php $this->options->keywords(); ?>">
<meta name="description" content="<?php $this->options->description(); ?>">
<meta property="og:title" content="<?php $this->archiveTitle([
  'category' => ('分类 %s 下的文章'),
  'search' => ('包含关键字 %s 的文章'),
  'tag' => ('标签 %s 下的文章'),
  'author' => ('%s 发布的文章')
], '', ' - '); ?><?php $this->options->title(); ?>">
<?php if ($this->is('post') || $this->is('page')): ?>
  <meta property="og:type" content="article">
<?php else: ?>
  <meta property="og:type" content="website">
<?php endif; ?>
<title>
  <?php $this->archiveTitle([
    'category' => ('分类 %s 下的文章'),
    'search' => ('包含关键字 %s 的文章'),
    'tag' => ('标签 %s 下的文章'),
    'author' => ('%s 发布的文章')
  ], '', ' - '); ?>
  <?php $this->options->title(); ?>
</title>
<?php 
// 加载所需css和js文件
$this->need('./public/include.php');
// 加载自定义头部代码
$this->options->customHeader();
// 主题设置：全局新窗口打开
if ($this->options->openInNewWindow === 'on') { ?>
  <base target="_blank" />
<?php } ?>
<script>
  /* 必须在首屏绘制前把三个属性写全，否则 CSS 变量取不到值会闪白：
     theme           主题色    —— 服务端输出
     color-scheme    明暗模式  —— 跟随本地存储，缺省随系统
     font-size-mode  字号      —— 跟随本地存储
     这三个变量的默认值需与 src/js/main/settings.js 中的 SETTINGS 保持一致 */
  (function () {
    var html = document.documentElement;
    html.setAttribute('theme', '<?= htmlspecialchars((string) $this->options->themeColor ?: 'blue', ENT_QUOTES, 'UTF-8') ?>');

    var mode = localStorage.getItem('theme');
    if (mode !== 'light' && mode !== 'dark') {
      mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    html.setAttribute('color-scheme', mode);

    var fontSize = localStorage.getItem('font-size');
    html.setAttribute(
      'font-size-mode',
      fontSize === 's' || fontSize === 'm' || fontSize === 'l' || fontSize === 'xl' ? fontSize : 'm'
    );
  })();
</script>
</head>