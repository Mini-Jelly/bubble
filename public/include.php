<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/* 版本查询串：dist 下的文件名固定不变，不加参数的话浏览器会长期缓存旧产物，
   主题更新后访客可能仍在用几天前的样式，且「改了没生效」的误判极难排查。
   参数由 getAssetVersion() 生成（版本号 + 文件 mtime），无需人工维护。 */
?>
<!-- 加载所需css和js文件 -->
<link rel="shortcut icon" href="<?php $this->options->themeUrl('assets/img/favicon/favicon.svg'); ?>"
  type="image/x-icon">
<link rel="stylesheet"
  href="<?php $this->options->themeUrl('dist/main.min.css'); ?>?v=<?= getAssetVersion('dist/main.min.css') ?>">
<!-- defer：内部逻辑全部绑定在 DOMContentLoaded 上，无需阻塞 HTML 解析 -->
<script defer
  src="<?php $this->options->themeUrl('dist/main.min.js'); ?>?v=<?= getAssetVersion('dist/main.min.js') ?>"></script>
<?php
// 轮播图只在首页第一页渲染，资源也只在那时加载，
// 否则文章页 / 归档页会白白下载约 95KB 的 swiper js+css
if ($this->is('index') && $this->getCurrentPage() === 1 && $this->options->swiper) { ?>
  <link rel="stylesheet"
    href="<?php $this->options->themeUrl('dist/swiper.min.css'); ?>?v=<?= getAssetVersion('dist/swiper.min.css') ?>">
  <script defer
    src="<?php $this->options->themeUrl('dist/swiper.min.js'); ?>?v=<?= getAssetVersion('dist/swiper.min.js') ?>"></script>
<?php } ?>
