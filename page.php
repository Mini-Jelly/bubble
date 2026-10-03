<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;
$this->need('./views/header.php');
?>

<body>
  <?php $this->need('./views/nav.php') ?>
  <div class="container">
    <div class="row">
      <main class="col-12" id="main">
        <div class="post line-numbers">
          <?php if ($this->options->postToc === 'on'): ?>
            <button id="tocToggleBtn" class="post-toc-toggle-btn" type="button" aria-controls="post-toc">目录</button>
          <?php endif; ?>
          <h1 class="post-title">
            <?php $this->title() ?>
          </h1>
          <?php $this->need('./views/post_meta.php'); ?>
          <div class="post-content">
            <?php echo wrapContentImages(getPostContent($this), $this->title); ?>
          </div>
        </div>
        <?php $this->need('./views/comment.php'); ?>
      </main>
    </div>
  </div>
  <?php $this->need('./views/footer.php'); ?>
