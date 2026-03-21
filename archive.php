<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;
$this->need('./public/header.php');
?>

<body>
  <?php $this->need('./public/nav.php'); ?>
  <div class="container">
    <div class="row">
      <main class="col-12 col-lg-10 offset-lg-1" id="main">
        <h1>
          <?php $this->archiveTitle(
            array(
              'category' => ('分类 “%s” 下的文章'),
              'search' => ('关键字 “%s” 的文章'),
              'tag' => ('标签 “%s” 下的文章'),
              'author' => ('由 “%s” 发布的文章')
            ),
            '',
            ''
          ); ?>
        </h1>
        <?php if ($this->have()): ?>
          <?php $this->need('./public/article.php'); ?>
        <?php else: ?>
          <h1>
            抱歉，你搜索的内容未找到，请尝试使用其他关键词搜索。
          </h1>
        <?php endif; ?>
        <?php $this->pageNav('&laquo; 前一页', '后一页 &raquo;','2','···'); ?>
      </main>
    </div>
  </div>
</body>
<?php $this->need('./public/footer.php'); ?>