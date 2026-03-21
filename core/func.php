<?php
/* 主题函数 */

/**
 * 计算距离上次更新的天数
 *
 * @param int $modified 最后更新时间的时间戳
 * @return int 返回距离上次更新的天数
 */
function getDaysSinceLastModified(int $modified): int
{
  $lastModifiedDate = date('Y-m-d', $modified);
  $currentDate = date('Y-m-d');
  return round((strtotime($currentDate) - strtotime($lastModifiedDate)) / 86400);
}

/**
 * 获取文章封面
 *
 * @param object $obj 传入包含文章信息的对象，通常为$this
 * @return string
 */
function getThumbnailLink($obj): string
{
  // 如果文章对象中已经包含了图片链接，则直接返回该链接
  if ($obj->fields->image)
    return $obj->fields->image;

  // 定义匹配图片标签的正则表达式
  $pattern = '/<img.*?src="(.*?)"[^>]*>/i';

  // 如果文章内容中存在图片标签，则使用正则表达式匹配获取图片链接
  if (preg_match($pattern, $obj->content, $matches))
    return $matches[1];

  //没有图片，返回透明像素顶替，减少判断逻辑
  return getTransparent1x1GIF();
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
 * 获取1x1的黑色遮罩(base64编码)图片链接
 *
 * @return string
 */
function get1x1Shade(): string
{
  return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkMAYAADkANVKH3ScAAAAASUVORK5CYII=";
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
  // 初始化值，值会被NULL覆盖，所以填什么值都没什么用
  $defaults = [
    'imgUrl'     => '',
    'title'      => '',
    'excerpt'    => '',
    'category'   => [],
    'time'       => '',
    'permalink'  => '#',
  ];
  // 将函数传递过来的数据覆盖当前默认值
  $data = array_merge($defaults, $data);
  // 将data变量导入当前域，用来给模板传递数据
  extract($data);
  // 记录当前缓冲区层级
  $initialLevel = ob_get_level();
  // 开启新的缓冲区
  ob_start();
  // 检查模板文件是否存在
  $templatePath = dirname(__DIR__) . '/template/article_card.php';
  if (!file_exists($templatePath)) {
    return '❌ 模板文件不存在: ' . $templatePath;
  }
  // 包含模板
  include $templatePath;
  // 获取当前缓冲区内容
  $html = ob_get_clean();
  // 恢复到初始缓冲区层级（避免影响外部）
  while (ob_get_level() > $initialLevel) {
    ob_end_clean();
  }
  return $html;
}

/**
 * 获取文章摘要
 * @param object $post 文章对象，通常为$this
 * @param int $length 截取摘要的长度
 * @param string $append 文章摘要结尾省略字符
 * @return string HTML 字符串
 */
function getArticleExcerpt($post, $length = 120, $append = '...'): string
{
  $excerpt = '';

  // 检查是否有自定义摘要字段
  if (isset($post->fields->excerpt) && !empty($post->fields->excerpt)) {
    $excerpt = $post->fields->excerpt;
  } elseif (!empty($post->excerpt)) {
    // 如果有 <!--more--> 分割的内容
    $excerpt = $post->excerpt;
  } else {
    // 从完整内容中提取
    $excerpt = $post->content;
  }

  // 清理 HTML 标签并截取
  $excerpt = strip_tags($excerpt);
  //Typecho_Common::subStr 是 Typecho 框架内置的一个字符串截取方法
  return Typecho_Common::subStr($excerpt, 0, $length, $append);
}
