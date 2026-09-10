<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/* 说明：这里原先挂了 itemprop="author" / itemprop="datePublished" 的 microdata，
 * 但外层从来没有 itemscope itemtype="BlogPosting"，孤立属性对搜索引擎无效，
 * 且 url 用的是 http://schema.org（早已统一到 https）。
 *
 * 现在结构化数据改为在 public/header.php 里输出一份完整的 JSON-LD BlogPosting
 * （headline / description / datePublished / dateModified / author / image），
 * 这是 Google 明确推荐、也是唯一被完整解析的格式。
 * 两套并存反而容易给出互相矛盾的信号，所以这里只保留语义化标签：
 * <time datetime> 与 rel="author"，不再重复声明微数据。 */
?>
<ul class="post-meta">
  <li>
    <a href="<?php $this->author->permalink(); ?>" rel="author">
      <?php $this->author(); ?>
    </a>
  </li>
  <li>·</li>
  <li>
    <time datetime="<?php $this->date('c'); ?>">
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
