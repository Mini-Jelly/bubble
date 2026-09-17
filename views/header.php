<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;

/* ------------------------------------------------------------------
 * 页面级信息准备
 * ------------------------------------------------------------------ */
$siteTitle = (string) $this->options->title;
$isSingle = $this->is('post') || $this->is('page');
$isIndex = $this->is('index');

// <title> 与 og:title 共用的完整标题。
// archiveTitle() 的 $end 必须传空串：该参数是给内联 echo 写法用的拼接符，
// 这里的返回值由下面自行拼接，传出分隔符会让标题出现两个「 - 」。
ob_start();
$this->archiveTitle([
  'category' => ('分类 %s 下的文章'),
  'search' => ('包含关键字 %s 的文章'),
  'tag' => ('标签 %s 下的文章'),
  'author' => ('%s 发布的文章')
], '', '');
$archiveTitle = trim((string) ob_get_clean());
$fullTitle = ($archiveTitle !== '' ? $archiveTitle . ' - ' : '') . $siteTitle;

// 描述与分享图：文章 / 独立页面取自身内容，其它页面退回站点全局值
$pageDescription = trim((string) $this->options->description);
$shareImage = '';
$shareImageAlt = '';

if ($isSingle) {
  $media = getArticleCardMedia($this, 120);
  if ($media['excerpt'] !== '') {
    $pageDescription = $media['excerpt'];
  }
  // 空字符串表示这篇文章没有封面。
  // 必须绝对化：og:image 的消费方是第三方抓取器，没有「当前页面」可作解析基准，
  // 根相对地址会被直接丢弃（分享卡片退化成无图）。
  if ($media['imgUrl'] !== '') {
    $shareImage = resolveResourceUrl($media['imgUrl'], (string) $this->options->siteUrl);
    $shareImageAlt = trim((string) $this->title);
  }
}

// canonical / og:url 必须指向页面自身：首页第 1 页用最干净的 siteUrl，其余重建。
// 必须用 getCurrentPage()：$this->currentPage 是 private 属性，传进全局函数
// 会被 Widget::__get() 静默吞成 null。
if ($isIndex && $this->getCurrentPage() === 1) {
  $canonical = (string) $this->options->siteUrl;
} else {
  $canonical = getArchivePermalink($this);
  if ($canonical === '') {
    $canonical = (string) $this->options->siteUrl;
  }
}

$escape = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="<?php $this->options->charset(); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#212529" media="(prefers-color-scheme: dark)">

  <title><?= $escape($fullTitle) ?></title>

  <!-- 基础索引信息 -->
  <link rel="canonical" href="<?= $escape($canonical) ?>">
  <meta name="description" content="<?= $escape($pageDescription) ?>">
  <?php if (trim((string) $this->options->keywords) !== ''): ?>
    <meta name="keywords" content="<?= $escape($this->options->keywords) ?>">
  <?php endif; ?>

  <!-- Open Graph -->
  <meta property="og:type" content="<?= $isSingle ? 'article' : 'website' ?>">
  <meta property="og:url" content="<?= $escape($canonical) ?>">
  <meta property="og:site_name" content="<?= $escape($siteTitle) ?>">
  <meta property="og:title" content="<?= $escape($archiveTitle !== '' ? $archiveTitle : $siteTitle) ?>">
  <meta property="og:description" content="<?= $escape($pageDescription) ?>">
  <?php if ($shareImage !== ''): ?>
    <meta property="og:image" content="<?= $escape($shareImage) ?>">
    <meta property="og:image:alt" content="<?= $escape($shareImageAlt) ?>">
  <?php endif; ?>
  <?php if ($isSingle): ?>
    <meta property="article:published_time" content="<?= date('c', $this->created) ?>">
    <?php if ($this->modified > $this->created): ?>
      <meta property="article:modified_time" content="<?= date('c', $this->modified) ?>">
    <?php endif; ?>
  <?php endif; ?>

  <!-- Twitter -->
  <meta name="twitter:card" content="<?= $shareImage !== '' ? 'summary_large_image' : 'summary' ?>">
  <meta name="twitter:title" content="<?= $escape($archiveTitle !== '' ? $archiveTitle : $siteTitle) ?>">
  <meta name="twitter:description" content="<?= $escape($pageDescription) ?>">
  <?php if ($shareImage !== ''): ?>
    <meta name="twitter:image" content="<?= $escape($shareImage) ?>">
    <meta name="twitter:image:alt" content="<?= $escape($shareImageAlt) ?>">
  <?php endif; ?>

  <?php if ($isSingle): ?>
    <!-- 结构化数据：文章页输出 BlogPosting -->
    <script type="application/ld+json">
      <?php
      // JSON_HEX_* 把 < > & ' " 转成 \uXXXX，既能安全嵌入 <script>，
      // 又不会像 htmlspecialchars 那样破坏 JSON 里的字面文本
      echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => (string) $this->title,
        'description' => $pageDescription,
        'datePublished' => date('c', $this->created),
        'dateModified' => date('c', $this->modified),
        'author' => [
          '@type' => 'Person',
          'name' => (string) $this->author->screenName,
          'url' => (string) $this->author->permalink,
        ],
        'mainEntityOfPage' => $canonical,
        'image' => $shareImage !== '' ? $shareImage : null,
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
      ?>
    </script>
  <?php endif; ?>

  <?php
  // 必须调用：它注入反垃圾 _ 令牌与 TypechoComment 对象，
  // 缺失会导致评论提交被 Security::protect() 退回、回复按钮报错。
  // description / keywords 已在上方自行输出，传空值避免重复。
  $this->header('description=&keywords=');
  ?>
  <?php
  // 加载所需css和js文件
  $this->need('./views/include.php');
  // 加载自定义头部代码
  $this->options->customHeader();

  // 站外链接新窗口开关：在 <html> 上打标记，由 main.js 只给站外链接补 target="_blank"。
  // 不能用 <base target="_blank">：它会把站内分页和锚点也一并变成新标签页。
  $openInNewWindow = ($this->options->openInNewWindow ?? 'off') === 'on' ? 'on' : 'off';

  // 访客个性化设置的后台默认值，与设置面板（views/fab.php）共用同一份，
  // 避免「面板显示的当前值」与「实际生效的值」对不上。
  $visitorDefaults = bubbleVisitorDefaults();
  ?>

  <?php
  /* 预取站内链接。跨文档转场能「走完」的前提是加载已经结束 ——
     没有预取时，从点击到新页面出现要等 200ms 到 2s，任何转场都会卡在半路，
     而这正是「把手机系统那套动画搬到网页上，总差一口气」的直接原因。
     eagerness: moderate 只在用户悬停 / 触摸按下时才真正发起请求，
     不会在页面一打开就把整站拉下来。
     排除后台与「新标签打开」的链接：前者是登录态页面、预取既无意义也浪费流量，
     后者根本不会导航当前文档。 */
  echo '<script type="speculationrules">'
    . json_encode([
      'prefetch' => [[
        'where' => [
          'and' => [
            ['href_matches' => '/*'],
            ['not' => ['href_matches' => '/admin/*']],
            ['not' => ['selector_matches' => '[target=_blank]']],
            ['not' => ['selector_matches' => '[download]']],
          ],
        ],
        'eagerness' => 'moderate',
      ]],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    . '</script>';
  ?>

  <script>
    /* 四个属性必须在首屏绘制前写完，否则 CSS 变量取不到值会闪白。
       theme              主题色
       color-scheme       明暗模式
       font-size-mode     字号
       font-family-mode   字体来源
       键名与 src/js/main/settings.js 的 SETTINGS 一一对应，改一处必须改两处。 */
    (function() {
      var html = document.documentElement;

      var DEFAULTS = {
        'theme-color': <?= json_encode($visitorDefaults['themeColor']) ?>,
        'theme': 'auto',
        'font-size': 'm',
        'font-family': <?= json_encode($visitorDefaults['fontFamily']) ?>
      };

      /* 白名单：属性值取不到对应 CSS 变量时，依赖它的整条声明会静默失效，宁可疑值退回默认值 */
      var ALLOWED = {
        'theme-color': <?= json_encode(array_keys(bubbleThemeColors())) ?>,
        'theme': ['auto', 'light', 'dark'],
        'font-size': <?= json_encode(array_keys(bubbleFontSizes())) ?>,
        'font-family': ['system', 'site']
      };

      function read(key) {
        var stored = null;
        try {
          stored = localStorage.getItem(key);
        } catch (e) {
          // Safari 无痕模式下 localStorage 可能直接抛错，不能让它中断整段初始化
        }
        return ALLOWED[key].indexOf(stored) !== -1 ? stored : DEFAULTS[key];
      }

      // 明暗：auto 只是访客的「意愿」，属性上只接受解析后的终值
      var mode = read('theme');
      if (mode === 'auto') {
        mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }

      html.setAttribute('theme', read('theme-color'));
      html.setAttribute('color-scheme', mode);
      html.setAttribute('font-size-mode', read('font-size'));
      html.setAttribute('font-family-mode', read('font-family'));
      html.setAttribute('open-new-window', '<?= $openInNewWindow ?>');
    })();
  </script>
</head>
