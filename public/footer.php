<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>

<footer class="footer">
  <div class="footer-content">
    <ul>
      <li>
        <a href="<?= $this->options->siteUrl(); ?>">首页</a>
      </li>
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
    <?php
    // 页面耗时 / 内存占用属于调试信息，默认不再对外输出。
    // 需要排查性能时到「开发者选项」里把「显示页面耗时」打开。
    if (($this->options->showPageUsage ?? 'off') === 'on'):
      $usageInfo = getPageUsage(); ?>
      <p>
        页面生成时间:<?= $usageInfo['page_generation_time'] ?>
        消耗内存:<?= $usageInfo['memory_consumed'] ?>
      </p>
    <?php endif; ?>
    <?php if($this->options->ICP){?>
      <p>备案号：<a href="https://beian.miit.gov.cn"><?= $this->options->ICP?></a></p>
    <?php } ?>
  </div>
</footer>
<?php
// 回到顶部按钮。老用户升级主题时该配置项还是 NULL，所以用 ?? 'on' 兜底
if (($this->options->backToTop ?? 'on') === 'on'): ?>
  <button type="button" id="backToTop" class="back-to-top" aria-label="回到顶部">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path d="M12 4l8 8h-5v8H9v-8H4z" />
    </svg>
  </button>
<?php endif; ?>
<?php $this->options->customFooter(); ?>
<?php $this->footer(); ?>
</body>

</html>