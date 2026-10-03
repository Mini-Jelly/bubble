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
          <p class="post-modified">
            <?php
            $modified = $this->modified;
            $daysSinceLastModified = getDaysSinceLastModified($modified);
            $modifiedDate = date('Y-m-d', $modified);
            // 当天更新时不显示“超过 0 天未更新”这种读起来别扭的文案
            echo $daysSinceLastModified > 0
              ? "本文最后更新于 {$modifiedDate}，已 {$daysSinceLastModified} 天未更新"
              : "本文最后更新于 {$modifiedDate}（今天）"; ?>
          </p>
          <div class="post-content">
            <?php echo wrapContentImages(getPostContent($this), $this->title); ?>
            <?php if (count($this->tags) !== 0) { ?>
              <div class="post-tags">
                标签：<?php $this->tags('', true, 'none'); ?>
              </div>
            <?php } ?>
            <?php $this->related(5)->to($relatedPosts);
            if ($relatedPosts->have()): ?>
              <h2>猜你喜欢😋</h2>
              <ul>
                <?php while ($relatedPosts->next()): ?>
                  <li>
                    <a href="<?php $relatedPosts->permalink(); ?>" title="<?php $relatedPosts->title(); ?>">
                      <?php $relatedPosts->title(); ?>
                    </a>
                  </li>
                <?php endwhile; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
        <?php $this->need('./views/comment.php'); ?>
      </main>
    </div>
  </div>
  <?php $this->need('./views/footer.php'); ?>
