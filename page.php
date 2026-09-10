<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;
$this->need('./public/header.php');
?>

<body>
  <?php $this->need('./public/nav.php') ?>
  <div class="container">
    <div class="row">
      <main class="col-12" id="main">
        <div class="post line-numbers">
          <button id="tocToggleBtn" class="post-toc-toggle-btn">目录</button>
          <h1 class="post-title">
            <?php $this->title() ?>
          </h1>
          <?php $this->need('./public/post_meta.php'); ?>
          <div class="post-content">
            <?php echo wrapContentImages($this->content, $this->title); ?>
            <?php $this->need('./public/post_copyright.php'); ?>
          </div>
        </div>
        <?php $this->need('./public/comment.php'); ?>
      </main>
    </div>
  </div>
  <?php $this->need('./public/footer.php'); ?>
</body>