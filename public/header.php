<?php if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;

/* ------------------------------------------------------------------
 * 页面级信息准备
 * ------------------------------------------------------------------ */
$siteTitle = (string) $this->options->title;
$isSingle = $this->is('post') || $this->is('page');
$isIndex = $this->is('index');

// 完整标题（如「分类 xxx 下的文章 - 站点名」），<title> 与 og:title 共用一份
// 用输出缓冲复用 archiveTitle() 的既有格式，保证标题格式不发生任何变化
ob_start();
$this->archiveTitle([
  'category' => ('分类 %s 下的文章'),
  'search' => ('包含关键字 %s 的文章'),
  'tag' => ('标签 %s 下的文章'),
  'author' => ('%s 发布的文章')
], '', ' - ');
$archiveTitle = trim((string) ob_get_clean());
$fullTitle = ($archiveTitle !== '' ? $archiveTitle . ' - ' : '') . $siteTitle;

// 描述与分享图：文章 / 独立页面取自身内容，其它页面退回站点全局值
$pageDescription = trim((string) $this->options->description);
$shareImage = '';

if ($isSingle) {
  $media = getArticleCardMedia($this, 120);
  if ($media['excerpt'] !== '') {
    $pageDescription = $media['excerpt'];
  }
  // 占位图不能作为分享封面
  if (!isPlaceholderImage($media['imgUrl'])) {
    $shareImage = $media['imgUrl'];
  }
}

// canonical / og:url：必须指向页面自身。
// 首页 page 1 用 siteUrl（最干净的那个地址），其余交给 getArchivePermalink() 重建。
// ⚠️ 这里用 getCurrentPage()（public 方法）而不是 $this->currentPage
//    ——后者是 Archive 的 private 属性，只在类作用域内（模板）可读，
//    一旦传进全局函数就会被 Widget::__get() 静默吞成 null。
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
/* Typecho 内置的头部输出。
 *
 * 主题此前完全没有调用 $this->header()，导致两个线上故障：
 *   1. 站点开启了「反垃圾保护」（commentsAntiSpam）时，评论表单缺少由它注入的
 *      _ 隐藏令牌，Feedback::comment() 里的 Security::protect() 会直接把提交退回，
 *      也就是说「评论发不出去」；
 *   2. 它同时输出 TypechoComment 对象，缺失时评论区的「回复 / 取消回复」点击即报错。
 * 另外还会带来 RSS / ATOM 自动发现等标准能力。
 *
 * description / keywords 已在上方自行输出，这里传空值避免重复。 */
$this->header('description=&keywords=');
?>
<?php
// 加载所需css和js文件
$this->need('./public/include.php');
// 加载自定义头部代码
$this->options->customHeader();
// 主题设置：站外链接新窗口打开。
// 原先这里输出的是 <base target="_blank">，它会把站内分页、目录锚点（#xxx）以及
// <a href="#"> 这类占位链接也一并变成新标签页，站内跳转体验彻底被破坏。
// 现在改成在 <html> 上打标记，由 main.js 只对站外链接补 target="_blank"。
$openInNewWindow = ($this->options->openInNewWindow ?? 'off') === 'on' ? 'on' : 'off';

// 访客级设置的「后台默认值」：访客在本机改过就由 localStorage 覆盖，没改过走这里。
// 两个来源的优先级在下面那段内联脚本里实现（见 settings.js 的分层说明）。
// 默认值统一由 bubbleVisitorDefaults() 计算，设置面板（public/fab.php）也用同一份，
// 避免「面板显示的当前值」与「实际生效的值」对不上。
$visitorDefaults = bubbleVisitorDefaults();
?>
<script>
  /* 必须在首屏绘制前把四个属性写全，否则 CSS 变量取不到值会闪白：
     theme              主题色     —— 访客选择优先，否则后台默认色
     color-scheme       明暗模式   —— 访客选择优先，否则跟随系统
     font-size-mode     字号       —— 访客选择优先，否则中号
     font-family-mode   字体来源   —— 访客选择优先，否则后台设置（默认系统字体）

     这四个键必须与 src/js/main/settings.js 的 SETTINGS 一一对应，改一处必须改两处。 */
  (function () {
    var html = document.documentElement;

    var DEFAULTS = {
      'theme-color': <?= json_encode($visitorDefaults['themeColor']) ?>,
      'theme': 'auto',
      'font-size': 'm',
      'font-family': <?= json_encode($visitorDefaults['fontFamily']) ?>
    };

    /* 白名单。属性值一旦取不到对应 CSS 变量，依赖它的整条声明会静默失效
       （主题此前就因漏定义 --link 出现过链接退化成继承色），所以宁可退回默认值。 */
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
        /* Safari 无痕模式下 localStorage 读写可能直接抛错，不能让它中断整段初始化 */
      }
      return ALLOWED[key].indexOf(stored) !== -1 ? stored : DEFAULTS[key];
    }

    /* 明暗：auto 只是访客的「意愿」，属性上只接受解析后的终值 */
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