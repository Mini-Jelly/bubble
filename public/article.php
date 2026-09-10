<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * 文章列表渲染（首页 / 归档 / 搜索共用）
 *
 * 置顶文章只在首页第一页渲染，且会在下面的列表循环里按 cid 跳过，
 * 避免同一个 cid 在同一页出现两次。
 */
$stickyCidList = [];

if ($this->is('index') && $this->getCurrentPage() === 1 && $this->options->stickyCid) {
    // 兼容 "1 || 2 || 3"、"1|2|3"、"1,2,3" 几种书写方式
    $stickyCidList = array_values(array_filter(
        array_map('intval', preg_split('/[\s|,]+/', (string) $this->options->stickyCid, -1, PREG_SPLIT_NO_EMPTY))
    ));

    foreach ($stickyCidList as $cid) {
        // 独立别名的 Widget_Archive，避免和主循环的列表实例互相污染
        $stickyPost = $this->widget('Widget_Archive@sticky-' . $cid, 'pageSize=1&type=post', 'cid=' . $cid);

        // 配置里填了不存在的 cid 时 widget 会返回 null，静默跳过即可
        if (!$stickyPost || !$stickyPost->cid) {
            continue;
        }

        $categoryData = array();
        foreach ($stickyPost->categories as $cat) {
            $categoryData[] = array(
                'name' => $cat['name'],
                'link' => $cat['permalink'],
            );
        }

        // 封面与摘要一次性算出，正文只解析一次
        $media = getArticleCardMedia($stickyPost);

        echo renderArticleCard([
            'imgUrl'    => $media['imgUrl'],
            'title'     => '[置顶]' . $stickyPost->title,
            'excerpt'   => $media['excerpt'],
            'category'  => $categoryData,
            'time'      => $stickyPost->date->format('Y-m-d'),
            'permalink' => $stickyPost->permalink,
        ]);
    }
}

// 文章数据构建
while ($this->next()):
    // 置顶文章已在上面输出过，这里跳过，否则同页会出现两张相同卡片
    if (in_array((int) $this->cid, $stickyCidList, true)) {
        continue;
    }

    // 分类数组
    $categoryData = array();
    foreach ($this->categories as $item) {
        $categoryData[] = array(
            'name' => $item['name'],
            'link' => $item['permalink'],
        );
    }

    // 封面与摘要一次性算出，正文只解析一次
    $media = getArticleCardMedia($this);

    echo renderArticleCard([
        'imgUrl'    => $media['imgUrl'],
        'title'     => $this->title,
        'excerpt'   => $media['excerpt'],
        'category'  => $categoryData,
        'time'      => $this->date->format('Y-m-d'),
        'permalink' => $this->permalink,
    ]);
endwhile;
