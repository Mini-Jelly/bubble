<?php
//自定义模板
/**
 * 最近修改的文章
 *
 * @package custom
 * 介绍：显示按修改时间排序的最新文章列表，带分页
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// 引入公共头部
$this->need('./public/header.php');
?>

<body>
    <?php $this->need('./public/nav.php'); ?>
    <div class="container">
        <div class="row">
            <main class="col-12 col-lg-10 offset-lg-1" id="main">

                <h1>最近修改的文章</h1>

                <?php
                // 获取当前页码
                $page = $this->request->get('page', 1);
                $pageSize = 10;
                $offset = ($page - 1) * $pageSize;
                $db = Typecho_Db::get();
                $select = $db->select(
                    'table.contents.cid',        // cid  - 必需
                    // 'table.contents.title',      // 标题
                    // 'table.contents.modified',   // 修改时间
                    // 'table.contents.created',    // 创建时间
                    'table.contents.type',       // 文章类型 - 必需
                )
                    ->from('table.contents')
                    ->where('table.contents.type = ?', 'post')
                    ->where('table.contents.status = ?', 'publish')
                    ->where('table.contents.password IS NULL OR table.contents.password = ""')
                    ->where('table.contents.modified <= ?', time())
                    ->limit($pageSize)
                    ->offset($offset)
                    ->order('table.contents.modified', Typecho_Db::SORT_DESC);

                $posts = $db->fetchAll($select);
                if (!empty($posts)):
                    foreach ($posts as $item) {
                        // 获取文章对象（这里$contentObject的内容蛮多的说实话）
                        $contentObject = $this->widget(
                            'Widget_Archive@post-' . $item['cid'],
                            'type=post',
                            'cid=' . $item['cid']
                        );
                        // 分类
                        $categories = $contentObject->categories;
                        $categoryData = array();
                        foreach ($categories as $i) {
                            $categoryData[] = array(
                                'name' => $i['name'],
                                'link' => $i['permalink']
                            );
                        }
                        //时间信息
                        $modified = $contentObject->modified;
                        $created = $contentObject->created;
                        if ($modified > $created) {
                            $time = date('Y-m-d', $created) . '（' . date('Y-m-d', $modified) . '更新了该文章）';
                        } elseif ($modified == $created) {
                            $time = date('Y-m-d', $created) . '（发布后从未更新）';
                        } else {
                            //处理非法情况
                            $time = date('Y-m-d', $created);
                        };

                        // 构建数据
                        $articleData = [
                            'imgUrl'     => getThumbnailLink($contentObject),
                            'title'      => $contentObject->title,
                            'excerpt'    => getArticleExcerpt($contentObject, 120, '...'),
                            'category'   => $categoryData,
                            'time'       => $time,
                            'permalink'  => $contentObject->permalink,
                        ];

                        // 输出文章卡片
                        echo renderArticleCard($articleData);
                    }

                    // 手动分页器
                    $total = $db->fetchRow($db->select(array('COUNT(*)' => 'num'))
                        ->from('table.contents')
                        ->where('table.contents.type = ?', 'post')
                        ->where('table.contents.status = ?', 'publish')
                        ->where('table.contents.password IS NULL OR table.contents.password = ""')
                        ->where('table.contents.modified <= ?', time()))['num'];

                    $totalPages = ceil($total / $pageSize);

                    if ($totalPages > 1): ?>
                        <ol class="page-navigator">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php
                                $siteUrl = $this->options->siteUrl . 'index.php/';
                                // $siteUrl = $this->options->siteUrl();
                                $url = $i == 1 ? $siteUrl . 'recent-modified.html' : $siteUrl . 'recent-modified.html?page=' . $i;
                                ?>
                                <li <?php if ($i == $page) echo 'class="current"'; ?>>
                                    <a href="<?= $url ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ol>
                    <?php endif; ?>
                <?php else: ?>
                    <h1>最近还没有什么新的更新哦~</h1>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
<?php $this->need('./public/footer.php'); ?>