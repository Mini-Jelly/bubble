<?php
/* 主题函数 */

/**
 * 计算距离上次更新的自然日天数
 *
 * @param int $modified 最后更新时间的时间戳
 * @return int 返回距离上次更新的天数，负数按 0 处理
 */
function getDaysSinceLastModified(int $modified): int
{
  // 先归一到当天零点再相减，避免跨时区/夏令时导致的 ±1 天误差
  $days = (strtotime('today') - strtotime('today', $modified)) / 86400;

  return max(0, (int) round($days));
}

/**
 * 取得「当前归档页自身」的规范链接（供 rel=canonical / og:url 使用）
 *
 * 注意不能直接用 $this->permalink：
 * Widget_Archive 会把查出来的文章行压进 $this->row，
 * 于是首页、分类、日期、搜索这些列表页上它返回的是「最后一篇文章」的地址
 * （实测首页拿到的是一篇随机文章，喂给 canonical 会直接误导搜索引擎）。
 *
 * ⚠️ 本函数是全局作用域函数，只能使用 Widget_Archive 的 public 方法，
 * 不能读 $archive->options / $archive->currentPage 这两个裸属性：
 *   - $options 是 protected、$currentPage 是 private；
 *   - 模板里 $this->options 之所以能用，是因为 need() 定义在 Widget\Archive 里，
 *     被 include 的模板继承了该类作用域；
 *   - 一旦离开类作用域（比如传进本函数），读取会落到 Widget::__get()，
 *     而它只认 row 键 / ___xxx() 魔术方法 / 插件钩子，两者都不满足 → 静默返回 null。
 * 实测后果：Router::url() 的 prefix 为空，URL 退化成相对路径
 * （/index.php/page/2/ 拿到 "/"，/index.php/2026/page/2/ 拿到 "/2026/"），
 * 同时分页判断也会因为 (int)null === 0 而整段失效。
 *
 * @param Widget_Archive $archive 归档 Widget，模板中即 $this
 * @return string 取不到时返回空字符串，由调用方决定兜底
 */
function getArchivePermalink($archive): string
{
  $archiveType = $archive->getArchiveType();

  // 单篇 / 独立页面 / 附件：permalink 本来就是对的
  if (in_array($archiveType, ['post', 'page', 'attachment'], true)) {
    return (string) $archive->permalink;
  }

  // Typecho 内部已为每种归档算好了「第 1 页」的规范地址：
  // index → siteUrl，category / tag / author / date / search → Router::url() 的结果。
  // getArchiveUrl() 是 public 方法，可以放心在类外调用。
  $baseUrl = (string) $archive->getArchiveUrl();

  // getCurrentPage() 是 public 方法（返回 int），不会踩上面那个坑
  $page = $archive->getCurrentPage();

  if ($page <= 1) {
    return $baseUrl !== '' ? $baseUrl : (string) Helper::options()->siteUrl;
  }

  // 第 2 页起才需要自己拼分页路由
  $routeMap = [
    'index'    => 'index_page',
    'category' => 'category_page',
    'tag'      => 'tag_page',
    'author'   => 'author_page',
    'search'   => 'search_page',
  ];

  if ('date' === $archiveType) {
    // 日期归档要按精度细分：年 / 年月 / 年月日，三种分页路由不同。
    // 注意 pageRow 里的 month / day 在「只到年」时是字符串 "00"，
    // 必须用 (int) 归一再判断，否则会误判成月归档。
    $pageRow = $archive->getPageRow();
    $month = (int) ($pageRow['month'] ?? 0);
    $day = (int) ($pageRow['day'] ?? 0);

    $route = $day > 0
      ? 'archive_day_page'
      : ($month > 0 ? 'archive_month_page' : 'archive_year_page');
  } elseif (isset($routeMap[$archiveType])) {
    $route = $routeMap[$archiveType];
  } else {
    // front（自定义首页）、404 等未知类型不做处理
    return $baseUrl;
  }

  // Router::url 会按路由声明的 params 取值，$pageRow 里多余的键会被忽略
  $pageRow = $archive->getPageRow();
  $pageRow['page'] = $page;

  $url = (string) Typecho_Router::url($route, $pageRow, Helper::options()->index);

  return $url !== '' ? $url : $baseUrl;
}

/**
 * 取文章正文（请求内缓存）
 *
 * Typecho 的 ___content() 既不把结果写回 row，也不做任何缓存：每访问一次
 * $post->content 就要重跑一次 Markdown / autoP 解析。同一篇文章在同一请求
 * 里往往会被取多次（SEO 描述、卡片封面与摘要、正文输出……），
 * 这里按 cid 记一份，保证一篇文章只解析一次。
 *
 * @param object $post 文章对象
 * @return string
 */
function getPostContent($post): string
{
  static $cache = [];

  $cid = (int) $post->cid;

  if ($cid <= 0) {
    return (string) $post->content;
  }

  if (!array_key_exists($cid, $cache)) {
    $cache[$cid] = (string) $post->content;
  }

  return $cache[$cid];
}

/**
 * 一次性计算文章卡片所需的「封面图 + 摘要」
 *
 * 之所以把两件事合并到一个函数里，是因为 Typecho 的 ___content() 并不会把结果
 * 写回 row —— 每一次访问 $post->content 都会重新跑一遍 Markdown / autoP 解析。
 * 原先「封面」和「摘要」是两个函数，各自取一次正文，首页 10 篇文章
 * 就是 20 次全文解析。这里统一只取一次。
 *
 * @param object $post   文章对象，通常为 $this 或 Widget_Archive 实例
 * @param int    $length 摘要截取长度
 * @param string $append 摘要结尾省略字符
 * @return array{imgUrl: string, excerpt: string}
 */
function getArticleCardMedia($post, int $length = 120, string $append = '...'): array
{
  // 自定义字段只查询一次（原实现里封面/摘要各查一次）
  $fields = $post->fields;
  $image = isset($fields->image) ? (string) $fields->image : '';
  $excerpt = isset($fields->excerpt) ? (string) $fields->excerpt : '';

  // 封面和摘要都有自定义值时，正文完全没有必要解析
  $content = ($image === '' || $excerpt === '') ? getPostContent($post) : '';

  if ($image === '') {
    // 退回正文里的第一张图；仍然没有则用透明像素顶替，减少调用方判断逻辑
    $image = preg_match('/<img\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $content, $matches)
      ? $matches[1]
      : getTransparent1x1GIF();
  }

  if ($excerpt === '') {
    // 与 Typecho 自身 excerpt 的语义保持一致：只取 <!--more--> 之前的部分
    $excerpt = strip_tags(explode('<!--more-->', $content, 2)[0]);
  }

  return [
    'imgUrl'  => $image,
    'excerpt' => Typecho_Common::subStr($excerpt, 0, $length, $append),
  ];
}

/**
 * 获取透明的1x1GIF(base64编码)的图片链接
 *
 * @return string
 */
function getTransparent1x1GIF(): string
{
  return "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
}

/**
 * 返回页面生成时间和页面消耗的内存
 * 
 * @return array 包含页面生成时间和页面消耗的内存的关联数组
 */
function getPageUsage(): array
{
  global $timeStart;

  // 获取当前时间戳（秒）
  $timeEnd = microtime(true);

  // 计算生成时间（单位：毫秒），保留3位小数
  $spendTime = number_format(($timeEnd - $timeStart) * 1000, 3);

  // 记录结束时的内存使用量
  $memoryEnd = memory_get_usage();

  // 计算消耗的内存量（以MB为单位）
  $memoryConsumed = number_format(($memoryEnd - $GLOBALS['memoryStart']) / 1048576, 3);

  return [
    'page_generation_time' => $spendTime . ' ms',
    'memory_consumed' => $memoryConsumed . ' MB'
  ];
}

/**
 * 设定全局变量用于计算内存使用情况
 * 
 * @return void
 */
function memoryUsageRecord(): void
{
  global $timeStart;

  $timeStart = microtime(true); // 获取当前时间戳（秒）

  // 记录内存使用量
  $GLOBALS['memoryStart'] = memory_get_usage();
}

/**
 * 渲染文章卡片（安全版）
 * @param array $data 包含文章信息的数组
 * @return string HTML 字符串
 */
function renderArticleCard(array $data): string
{
  // 默认值只用来兜住缺失的键（null 会覆盖默认值，因此允许显式传 null）
  $data = array_merge([
    'imgUrl'    => '',
    'title'     => '',
    'excerpt'   => '',
    'category'  => [],
    'time'      => '',
    'permalink' => '#',
  ], $data);

  // 显式解构出模板需要的变量。原先用 extract() 会把 $data / $templatePath
  // 这类内部变量一起炸进作用域，模板里一旦出现同名键就会互相覆盖。
  ['imgUrl' => $imgUrl, 'title' => $title, 'excerpt' => $excerpt,
    'category' => $category, 'time' => $time, 'permalink' => $permalink] = $data;

  $templatePath = dirname(__DIR__) . '/template/article_card.php';

  // 模板缺失属于部署错误：写日志即可，不要把服务器绝对路径回显到页面上
  if (!is_file($templatePath)) {
    error_log('[bubble] 文章卡片模板缺失: ' . $templatePath);
    return '<!-- bubble: template/article_card.php 不存在 -->';
  }

  $initialLevel = ob_get_level();
  ob_start();

  try {
    include $templatePath;
    // 正常路径：取走缓冲区内容
    return (string) ob_get_clean();
  } catch (Throwable $e) {
    // 异常路径：先清理自己开的缓冲区，否则残留的输出缓冲区会吞掉整个页面后续输出
    while (ob_get_level() > $initialLevel) {
      ob_end_clean();
    }
    throw $e;
  }
}

/**
 * 将正文里的 <img> 包装成 spotlight 灯箱链接
 *
 * 图片统一使用原生 loading="lazy"（现代浏览器均已支持，老浏览器会退化为
 * 立即加载，不会出错），因此不再依赖 lazysizes。
 *
 * @param string $content     已渲染的文章正文
 * @param string $fallbackAlt 图片缺少 alt 时使用的兜底文本，通常传文章标题
 * @return string
 */
function wrapContentImages(string $content, string $fallbackAlt = ''): string
{
  // 匹配整个 img 标签（[^>] 天然支持跨行，无需 s 修饰符）
  return preg_replace_callback('/<img\b[^>]*>/i', function ($matches) use ($fallbackAlt) {
    $imgTag = $matches[0];

    // 已被其它插件处理过（带 loading 属性或已包进 <a>），不重复包装
    if (stripos($imgTag, 'loading=') !== false) {
      return $imgTag;
    }

    // 取不到 src 时保持原样
    if (!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $imgTag, $srcMatch)) {
      return $imgTag;
    }

    // 保留图片自身的 alt，只有缺失时才回退到文章标题，
    // 否则整篇文章所有图片共用一个 alt，对 SEO 和无障碍都是负优化
    $alt = preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/i', $imgTag, $altMatch)
      ? $altMatch[1]
      : $fallbackAlt;

    $url = htmlspecialchars($srcMatch[1], ENT_QUOTES, 'UTF-8');
    $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');

    return sprintf(
      '<a href="%s" class="spotlight" data-title="false">' .
        '<img src="%s" loading="lazy" decoding="async" alt="%s" title="点击放大图片">' .
        '</a>',
      $url,
      $url,
      $alt
    );
  }, $content);
}
