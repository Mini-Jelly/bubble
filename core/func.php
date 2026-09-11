<?php
/* 主题函数 */

/**
 * 主题色选项表：value => 中文名
 *
 * 后台设置页（core/config.php）与前台「个性化设置」面板（views/fab.php）
 * 必须使用同一份名单：面板会把每个 value 输出成色块，漏一个访客就少一个可选色，
 * 多一个则是后台选不出来的「幽灵项」。因此抽到这里做唯一真源。
 *
 * @return array<string, string>
 */
function bubbleThemeColors(): array
{
  return [
    'blue'   => '蓝色',
    'red'    => '红色',
    'pink'   => '粉红色',
    'grape'  => '葡萄色',
    'violet' => '紫罗兰色',
    'indigo' => '靛蓝色',
    'cyan'   => '蓝绿色',
    'teal'   => '鸭绿色',
    'green'  => '绿色',
    'lime'   => '酸橙绿色',
    'yellow' => '黄色',
    'orange' => '橘色',
  ];
}

/**
 * 正文字号档位表：value => 中文名
 *
 * 顺序即滑条的档位顺序（由小到大），前台滑条按下标取值，
 * 因此这里不能随意调序，调整顺序等同于调整 UI 语义。
 *
 * @return array<string, string>
 */
function bubbleFontSizes(): array
{
  return [
    's'  => '小',
    'm'  => '中',
    'l'  => '大',
    'xl' => '特大',
  ];
}

/**
 * 访客级设置的「后台默认值」
 *
 * 优先级模型固定为：访客在本机做出的选择（localStorage）> 这里返回的后台默认值。
 * header.php 的首屏内联脚本与 views/fab.php 的设置面板都要用，
 * 集中在这里才不会出现「面板显示的当前值」与「实际生效的值」对不上。
 *
 * 明暗与字号没有对应的后台配置项，默认值写死在调用方（auto / m）。
 *
 * @return array{themeColor: string, fontFamily: string}
 */
function bubbleVisitorDefaults(): array
{
  $options = Helper::options();

  $themeColor = (string) ($options->themeColor ?: 'blue');
  if (!array_key_exists($themeColor, bubbleThemeColors())) {
    // 数据库里可能残留已废弃的颜色名，兜底成默认色，
    // 否则会把一个没有 CSS 变量对应的值写进 html[theme]
    $themeColor = 'blue';
  }

  return [
    'themeColor' => $themeColor,
    // 老用户升级主题后该配置项为 NULL，不能直接和 'site' 严格比较
    'fontFamily' => ($options->fontFamily ?? 'system') === 'site' ? 'site' : 'system',
  ];
}

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
 * 判断封面地址是不是「没有封面」的占位图
 *
 * getArticleCardMedia() 在文章没有任何图片时会退回一张 1x1 透明 GIF
 * （data:image 开头的 base64），好处是调用方不必到处判空。但真正需要
 * 「要一张能看的封面」的场景（卡片渲染、og:image），还是得把占位图挑出来。
 *
 * 原先两处都裸写 strpos($url, 'data:image') !== 0 —— 一个否定式 strpos
 * 配 if，读起来要先在脑子里取两次反，很容易看错。这里给这个判断一个
 * 说得出口的名字，调用方写 !isPlaceholderImage($url) 即可自解释。
 *
 * @param string $url 封面地址
 * @return bool true = 空地址或占位图，即没有可展示的封面
 */
function isPlaceholderImage(string $url): bool
{
  return '' === $url || 0 === strpos($url, 'data:image');
}

/**
 * 把配置里填写的资源地址补全成可直接使用的绝对地址
 *
 * 背景：轮播图这类配置项允许写相对路径。而相对路径的解析基准是「当前页面的
 * 地址」，于是同一张图会出现「首页正常、分页和文章页 404」的怪象：
 *   首页  http://localhost/                     → http://localhost/usr/...            ✅
 *   分页  http://localhost/index.php/page/2/    → http://localhost/index.php/page/2/usr/...  ❌
 * 实测分页下图片请求会落到 Typecho 的 404 页（响应体标题就是「页面没找到」），
 * 而首页之所以看着正常，纯粹因为 URL 恰好与站点根重合。
 *
 * 这里统一按站点根补全，对数据库里的存量配置同样生效，无需用户重新保存设置。
 *
 * @param string $url  配置中原样填写的地址
 * @param string $base 补全基准，通常传站点根地址
 * @return string 空串与已是绝对形式的地址都原样返回
 */
function resolveResourceUrl(string $url, string $base): string
{
  $url = trim($url);

  if ('' === $url) {
    return '';
  }

  // 已绝对（http://、https://）、协议相对（//cdn.example.com）、
  // 特殊协议（data:、mailto:、tel:）以及页内锚点，一律原样返回
  if (
    preg_match('#^(?:[a-z][a-z0-9+.\-]*:)?//#i', $url)
    || preg_match('#^(?:data|mailto|tel):#i', $url)
    || '#' === $url[0]
  ) {
    return $url;
  }

  return (string) Typecho_Common::url($url, $base);
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

  $templatePath = dirname(__DIR__) . '/views/article_card.php';

  // 模板缺失属于部署错误：写日志即可，不要把服务器绝对路径回显到页面上
  if (!is_file($templatePath)) {
    error_log('[bubble] 文章卡片模板缺失: ' . $templatePath);
    return '<!-- bubble: views/article_card.php 不存在 -->';
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

/**
 * 读取主题版本号（取 index.php 主题头注释里的 @version）
 *
 * index.php 是 Typecho 的主题入口，其头部注释里的 @version 由
 * update_version.js 从 package.json 同步而来，本来就是版本号的唯一真源。
 * 这里不另立真源，只做一次解析并缓存。
 *
 * @return string 解析失败时返回 '0'，调用方无需判空
 */
function getThemeVersion(): string
{
  static $version = null;

  if ($version !== null) {
    return $version;
  }

  $version = '0';
  $entry = dirname(__DIR__) . '/index.php';

  if (is_readable($entry)) {
    // 只读头部 1KB：主题头注释一定在最前面，没必要把整个模板读进内存
    $head = (string) file_get_contents($entry, false, null, 0, 1024);

    if (preg_match('/@version\s+(\S+)/', $head, $matches)) {
      $version = $matches[1];
    }
  }

  return $version;
}

/**
 * 给 dist 下的静态资源生成缓存击穿参数
 *
 * 主题更新后文件名不变（始终是 main.min.css），浏览器会长期命中旧产物，
 * 表现为「明明改了却没生效」，而且很难自查 —— 本轮排查前端问题时，
 * headless 实例就一直在跑几天前的旧 CSS。
 *
 * 组成：版本号 + 文件 mtime
 *   @version  随发版递增，保证跨版本一定失效
 *   mtime     每次重新构建都会变，保证同一版本内的多次构建也立刻生效，
 *             且不需要人工维护任何额外的版本号
 *
 * @param string $relativePath 相对主题根目录的路径，如 'dist/main.min.css'
 * @return string
 */
function getAssetVersion(string $relativePath): string
{
  $file = dirname(__DIR__) . '/' . ltrim($relativePath, '/');

  // dist/ 尚未构建（例如刚克隆下来的仓库）时退回纯版本号，不要输出 "?v=1.1.0.0"
  if (!is_file($file)) {
    return getThemeVersion();
  }

  return getThemeVersion() . '.' . filemtime($file);
}
