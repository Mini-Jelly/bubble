<?php
//自定义模板
/**
 * 最近修改的文章
 *
 * @package custom
 * 介绍：显示按修改时间排序的最新文章列表，带分页
 *
 * 注意：Typecho 的 Widget_Archive::execute() 把排序硬编码成 created DESC
 * （见 var/Widget/Archive.php），要按 modified 排序只能自己发 SQL，
 * 拿到 cid 后再逐条构造 Widget_Archive 来取标题 / 分类 / permalink。
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// 引入公共头部
$this->need('./public/header.php');

// 本页真实地址：从独立页面列表里反查 permalink。
// 原先写死 index.php/recent-modified.html，站点开启伪静态后分页链接会直接 404。
$pageUrl = '';
$this->widget('Widget_Contents_Page_List@recent-page-list')->to($pageList);
while ($pageList->next()) {
    if ($pageList->slug === 'recent-modified') {
        $pageUrl = $pageList->permalink;
        break;
    }
}
// 兜底：页面还没建或者改了缩略名时，至少不要拼出错误链接
if ($pageUrl === '') {
    $pageUrl = $this->options->siteUrl;
}

?>

<body>
    <?php $this->need('./public/nav.php'); ?>
    <div class="container">
        <div class="row">
            <main class="col-12 col-lg-10 offset-lg-1" id="main">

                <h1>最近修改的文章</h1>

                <?php
                // 获取当前页码
                $page = max(1, (int) $this->request->get('page', 1));
                $pageSize = 10;
                $offset = ($page - 1) * $pageSize;
                $db = Typecho_Db::get();

                /**
                 * 统一的筛选条件。
                 *
                 * created <= now 这一条不能省：Typecho 的定时发布文章 status 同样是
                 * publish，仅靠 status 判断会让「未来才该出现」的文章提前泄露出来。
                 */
                $applyFilters = function ($query) {
                    return $query
                        ->where('table.contents.type = ?', 'post')
                        ->where('table.contents.status = ?', 'publish')
                        ->where('table.contents.password IS NULL OR table.contents.password = ""')
                        ->where('table.contents.created <= ?', time())
                        ->where('table.contents.modified <= ?', time());
                };

                $select = $applyFilters($db->select(
                    'table.contents.cid',
                    'table.contents.title',
                    'table.contents.created',
                    'table.contents.modified'
                )->from('table.contents'))
                    ->limit($pageSize)
                    ->offset($offset)
                    ->order('table.contents.modified', Typecho_Db::SORT_DESC);

                $posts = $db->fetchAll($select);

                if (!empty($posts)):
                    foreach ($posts as $item) {
                        // 构造 Widget_Archive 以取得 permalink / 分类 / 正文
                        $contentObject = $this->widget(
                            'Widget_Archive@post-' . $item['cid'],
                            'type=post',
                            'cid=' . $item['cid']
                        );

                        // 分类
                        $categoryData = array();
                        foreach ($contentObject->categories as $category) {
                            $categoryData[] = array(
                                'name' => $category['name'],
                                'link' => $category['permalink'],
                            );
                        }

                        // 时间信息
                        $modified = $contentObject->modified;
                        $created = $contentObject->created;
                        if ($modified > $created) {
                            $time = date('Y-m-d', $created) . '（' . date('Y-m-d', $modified) . '更新了该文章）';
                        } elseif ($modified == $created) {
                            $time = date('Y-m-d', $created) . '（发布后从未更新）';
                        } else {
                            // 处理非法情况
                            $time = date('Y-m-d', $created);
                        }

                        // 封面与摘要一次性算出，正文只解析一次
                        $media = getArticleCardMedia($contentObject);

                        // 输出文章卡片
                        echo renderArticleCard([
                            'imgUrl'    => $media['imgUrl'],
                            'title'     => $contentObject->title,
                            'excerpt'   => $media['excerpt'],
                            'category'  => $categoryData,
                            'time'      => $time,
                            'permalink' => $contentObject->permalink,
                        ]);
                    }

                    // 手动分页器
                    $total = (int) $db->fetchRow(
                        $applyFilters($db->select(array('COUNT(*)' => 'num'))->from('table.contents'))
                    )['num'];

                    $totalPages = (int) ceil($total / $pageSize);

                    if ($totalPages > 1): ?>
                        <ol class="page-navigator">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php
                                // 第 1 页用裸地址，其余页用 query 参数，避免出现两套「同一个页面」的 URL
                                $url = $i === 1
                                    ? $pageUrl
                                    : $pageUrl . (strpos($pageUrl, '?') === false ? '?' : '&') . 'page=' . $i;
                                ?>
                                <li <?php if ($i === $page) echo 'class="current"'; ?>>
                                    <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ol>
                    <?php endif; ?>
                <?php else: ?>
                    <h2>最近还没有什么新的更新哦~</h2>
                <?php endif; ?>
            </main>
        </div>
    </div>
<?php $this->need('./public/footer.php'); ?>
