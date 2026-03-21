<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>

<footer class="footer">
  <div class="footer-content">
    <ul>
      <li>
        <a href="<?= $this->options->siteUrl(); ?>">首页</a>
      </li>
      <!-- TODO 如果数量很多会溢出，需要一个更合理的样式，特别是手机端 -->
      <?php $this->widget('Widget_Contents_Page_List')->to($pages);
      while ($pages->next()): ?>
        <li><a href="<?= $pages->permalink(); ?>" title="<?= $pages->title(); ?>"><?= $pages->title(); ?></a></li>
      <?php endwhile; ?>
      <li><a id="post-rss" href="<?php $this->options->feedUrl(); ?>"><?php _e('文章 RSS'); ?></a></li>
      <li><a id="comments-rss" href="<?php $this->options->commentsFeedUrl(); ?>"><?php _e('评论 RSS'); ?></a></li>
    </ul>
    <p>
      Copyright ©<?= date('Y'); ?>
      <a href="<?= $this->options->siteUrl(); ?>"><?= $this->options->title(); ?></a>
      Power by <a href="https://www.typecho.org" target="_blank">Typecho</a>
    </p>
    <p>
      <?php $usageInfo = getPageUsage();
      echo '页面生成时间:' . $usageInfo['page_generation_time']
        . ' 消耗内存:' . $usageInfo['memory_consumed']; ?>
    </p>
    <?php if($this->options->ICP){?>
      <p>备案号：<a href="https://beian.miit.gov.cn"><?= $this->options->ICP?></a></p>
    <?php } ?>
  </div>
</footer>
<?php $this->options->customFooter(); ?>
<?php $this->footer(); ?>
</body>

</html>