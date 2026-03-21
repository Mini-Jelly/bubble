<ul class="post-meta">
  <li itemprop="author" itemscope itemtype="http://schema.org/Person">
    <a itemprop="name" href="<?php $this->author->permalink(); ?>" rel="author">
      <?php $this->author(); ?>
    </a>
  </li>
  <li>·</li>
  <li>
    <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished">
      <?php $this->date(); ?>
    </time>
  </li>
  <?php
    //判断是否有分类
    if (trim($this->category) !== "") { ?>
  <li>·</li>
  <li>
    <?php $this->category(','); ?>
  </li>
  <?php } ?>
</ul>