<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
// 卡片模板，只负责将数据放到对应的位置上
// 没有封面时 getArticleCardMedia() 会返回 data:image 占位图，据此判断
$hasImage = strpos($imgUrl, 'data:image') !== 0;
?>
<article class="article-card">
    <div class="article-card-image">
        <a href="<?= htmlspecialchars($permalink) ?>" title="<?= htmlspecialchars($title) ?>">
            <?php if ($hasImage): ?>
                <!-- width/height 用于预留位置，避免图片加载后布局跳动（CLS） -->
                <img src="<?= htmlspecialchars($imgUrl) ?>" loading="lazy" decoding="async"
                    width="266" height="169" alt="<?= htmlspecialchars($title) ?>">
            <?php else: ?>
                <div class="no-image" aria-hidden="true"></div>
            <?php endif; ?>
        </a>
    </div>

    <div class="article-card-info">
        <header class="article-card-info-header">
            <!-- 列表页会有十几张卡片，这里只能是 h2：用 h1 会让整页标题层级完全乱掉 -->
            <h2>
                <a href="<?= htmlspecialchars($permalink) ?>"><?= htmlspecialchars($title) ?></a>
            </h2>
        </header>

        <div class="article-card-info-excerpt">
            <p><?= htmlspecialchars($excerpt) ?></p>
        </div>

        <footer class="article-card-info-footer">
            <span>
                <?php if (!empty($category)):
                    $categoryLinks = array();
                    foreach ($category as $item) {
                        $name = htmlspecialchars($item['name']);
                        $link = htmlspecialchars($item['link']);
                        $categoryLinks[] = '<a href="' . $link . '">' . $name . '</a>';
                    }
                    echo implode('・', $categoryLinks) . '・';
                endif;
                echo htmlspecialchars($time) ?>
            </span>
        </footer>
    </div>
</article>
