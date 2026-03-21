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
          <h1 class="post-title">
            <?php $this->title() ?>
          </h1>
          <?php $this->need('./public/post_meta.php'); ?>
          <p class="post-modified">
            <?php
            $modified = $this->modified;
            $daysSinceLastModified = getDaysSinceLastModified($modified);
            echo "本文最后更新于 " . date('Y-m-d', $modified) . "，超过 $daysSinceLastModified 天未更新"; ?>
          </p>
          <div class="post-content">
            <?php
            $pattern = '/<img.*?src=\"(.*?)\"[^>]*>/i';
            $replacement = '<a href="$1" class="spotlight" data-title="false"><img class="lazyload" data-src="$1" alt="' . $this->title . '" title="点击放大图片" ></a>';
            $content = preg_replace($pattern, $replacement, $this->content);
            echo $content; ?>
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
            <?php $this->need('./public/post_copyright.php'); ?>
          </div>
        </div>
        <?php $this->need('./public/comment.php'); ?>
      </main>
    </div>
  </div>
  <?php $this->need('./public/footer.php'); ?>
</body>