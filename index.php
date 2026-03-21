<?php

/**
 * bubble Beat 20260204
 * @package Bubble
 * @author Mini-Jelly
 * @version Beat 20260204
 * @link https://jjj8.top
 */
if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;
$this->need('./public/header.php'); ?>

<body>
  <?php $this->need('./public/nav.php'); ?>
  <div class="container">
    <div class="row">
      <main class="col-12 col-lg-10 offset-lg-1">
        <?php if ($this->currentPage === 1) {
          if ($this->options->swiper) {
            $this->need('./public/swiper.php');
          }
          if ($this->options->tagCloud === 'on') {
            $this->need('./public/tag_cloud.php');
          }
        }
        $this->need('./public/article.php');
        $this->pageNav('&laquo; 前一页', '后一页 &raquo;', '2', '···'); ?>
      </main>
    </div>
  </div>
  <?php $this->need('./public/footer.php'); ?>
</body>