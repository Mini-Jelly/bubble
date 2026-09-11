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
?>
<script>
  /* 必须在首屏绘制前把三个属性写全，否则 CSS 变量取不到值会闪白：
     theme           主题色    —— 服务端输出
     color-scheme    明暗模式  —— 跟随本地存储，缺省随系统
     font-size-mode  字号      —— 跟随本地存储
     这三个变量的默认值需与 src/js/main/settings.js 中的 SETTINGS 保持一致 */
  (function () {
    var html = document.documentElement;
    html.setAttribute('theme', '<?= htmlspecialchars((string) $this->options->themeColor ?: 'blue', ENT_QUOTES, 'UTF-8') ?>');
    html.setAttribute('open-new-window', '<?= $openInNewWindow ?>');

    var mode = localStorage.getItem('theme');
    if (mode !== 'light' && mode !== 'dark') {
      mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    html.setAttribute('color-scheme', mode);

    var fontSize = localStorage.getItem('font-size');
    html.setAttribute(
      'font-size-mode',
      fontSize === 's' || fontSize === 'm' || fontSize === 'l' || fontSize === 'xl' ? fontSize : 'm'
    );
  })();
</script>
</head>