<?php $this->widget('Widget_Metas_Tag_Cloud', 'sort=mid&ignoreZeroCount=1&desc=0&limit=45')->to($tags); ?>
<!-- 当有标签时则输出标签，没有标签则不进入该代码，即什么都不做 -->
<?php if ($tags->have()) { ?>
  <div class="tag-cloud">
    <h1>🥳标签云</h1>
    <div class="tag-cloud-list">
      <?php while ($tags->next()): ?>
        <a href="<?php $tags->permalink(); ?>" rel="tag" class="size-<?php $tags->split(5, 10, 20, 30); ?>"
          title="<?php $tags->count(); ?> 个相关"><?php $tags->name(); ?></a>
      <?php endwhile; ?>
    </div>
  </div>
<?php } ?>