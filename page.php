<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;
$this->need('./public/header.php');

// 老用户升级主题时新增配置项为 NULL，这里必须兜底成默认值，否则功能会静默失效
$tocEnabled = ($this->options->postToc ?? 'on') === 'on';
?>

<body>
  <?php $this->need('./public/nav.php') ?>
  <div class="container">
    <div class="row">
      <main class="col-12" id="main">
        <div class="post line-numbers">
          <?php if ($tocEnabled): ?>
            <button id="tocToggleBtn" class="post-toc-toggle-btn" type="button" aria-controls="post-toc">目录</button>
          <?php endif; ?>
          <h1 class="post-title">
            <?php $this->title() ?>
          </h1>
          <?php $this->need('./public/post_meta.php'); ?>
          <div class="post-content">
            <?php echo wrapContentImages(getPostContent($this), $this->title); ?>
          </div>
        </div>
        <?php $this->need('./public/comment.php'); ?>
      </main>
    </div>
  </div>
  <?php $this->need('./public/footer.php'); ?>
