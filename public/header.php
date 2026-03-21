<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>
<!DOCTYPE HTML>
<html lang="zh-cn">
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
  // 主题设置：设置默认主题颜色
  const html = document.documentElement;
  html.setAttribute('theme', '<?= $this->options->themeColor; ?>');
</script>
</head>