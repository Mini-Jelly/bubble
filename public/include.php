<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!-- 加载所需css和js文件 -->
<link rel="shortcut icon" href="<?php $this->options->themeUrl('assets/img/favicon/favicon.svg'); ?>"
  type="image/x-icon">
<link rel="stylesheet" href="<?php $this->options->themeUrl('dist/main.min.css'); ?>">
<!-- defer：内部逻辑全部绑定在 DOMContentLoaded 上，无需阻塞 HTML 解析 -->
<script defer src="<?php $this->options->themeUrl('dist/main.min.js'); ?>"></script>
<?php
// 轮播图只在首页第一页渲染，资源也只在那时加载，
// 否则文章页 / 归档页会白白下载约 95KB 的 swiper js+css
if ($this->is('index') && $this->getCurrentPage() === 1 && $this->options->swiper) { ?>
  <link rel="stylesheet" href="<?php $this->options->themeUrl('dist/swiper.min.css'); ?>">
  <script defer src="<?php $this->options->themeUrl('dist/swiper.min.js'); ?>"></script>
<?php } ?>
