<?php
// $this->need('./utility/resource.php');
$swiper = [];
$swiper_text = $this->options->swiper;
$swiper_arr = array_map('trim', explode("\r\n", $swiper_text));
foreach ($swiper_arr as $item) {
  list($img, $url, $title) = array_map('trim', explode("||", $item));
  $swiper[] = compact('img', 'url', 'title');
  }
?>
<div class="swiper">
  <div class="swiper-wrapper">
    <?php
    foreach ($swiper as $slide) {
      ?>
      <div class="swiper-slide">
        <a href="<?= $slide['url']; ?>">
          <img loading="lazy" alt="Loading..." src="<?= getTransparent1x1GIF(); ?>" srcset="<?= $slide['img']; ?>">
          <div class="swiper-lazy-preloader"></div>
        </a>
      </div>
    <?php } ?>
  </div>
  <div class="swiper-pagination"></div>
</div>