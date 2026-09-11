<?php

/**
 * bubble 1.1.0
 * @package Bubble
 * @author Mini-Jelly
 * @version 1.1.0
 * @build 20260911
 * @link https://jjj8.top
 */

//  TODO 演进方向──>vite + pnpm 构建项目 
if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;
$this->need('./views/header.php'); ?>

<body>
  <?php $this->need('./views/nav.php'); ?>
  <div class="container">
    <div class="row">
      <main class="col-12 col-lg-10 offset-lg-1">
        <?php if ($this->getCurrentPage() === 1) {
          if ($this->options->swiper) {
            $this->need('./views/swiper.php');
          }
          // 老用户升级主题后新增配置项为 NULL，直接和 'on' 比较会导致标签云静默消失
          if (($this->options->tagCloud ?? 'on') === 'on') {
            $this->need('./views/tag_cloud.php');
          }
        }
        $this->need('./views/article.php');
        $this->pageNav('&laquo; 前一页', '后一页 &raquo;', '2', '···'); ?>
      </main>
    </div>
  </div>
  <?php $this->need('./views/footer.php'); ?>
