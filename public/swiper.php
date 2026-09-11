<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$swiper = [];
// 站内相对地址的补全基准。配置里允许写相对路径，不补全的话在分页、文章页这类
// 多级路径下会被浏览器按「当前目录」解析，图片必 404（详见 resolveResourceUrl）。
$swiperBase = (string) $this->options->siteUrl;
// 兼容 CRLF / LF / CR 三种换行，原先只按 \r\n 切会导致 LF 配置整段粘成一行
$lines = preg_split('/\r\n|\r|\n/', (string) $this->options->swiper, -1, PREG_SPLIT_NO_EMPTY);
foreach ($lines as $line) {
  // 格式：图片链接 || 跳转链接 || 跳转文字
  // 字段不足时用空串补齐，避免原先 list() 解构直接抛 undefined offset
  $parts = array_map('trim', explode('||', $line));
  $img = $parts[0] ?? '';
  if ($img === '') {
    continue; // 丢弃没有图片的配置行
  }
  $swiper[] = [
    'img'   => resolveResourceUrl($img, $swiperBase),
    'url'   => resolveResourceUrl($parts[1] ?? '', $swiperBase),
    'title' => $parts[2] ?? '',
  ];
}
?>
<div class="swiper">
  <div class="swiper-wrapper">
    <?php foreach ($swiper as $index => $slide) { ?>
      <div class="swiper-slide">
        <a href="<?= htmlspecialchars($slide['url']) ?>">
          <!--
            原先写成 src=透明图 + srcset=真实图，srcset 并不用于这个场景，
            图片依然会立即下载；且 alt="Loading..." 是无效的替代文本。
            首屏第一张参与 LCP，应优先加载不做懒加载。
          -->
          <img src="<?= htmlspecialchars($slide['img']) ?>"
            alt="<?= htmlspecialchars($slide['title']) ?>"
            width="947" height="250"
            decoding="async"
            <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
        </a>
      </div>
    <?php } ?>
  </div>
  <div class="swiper-pagination"></div>
</div>
