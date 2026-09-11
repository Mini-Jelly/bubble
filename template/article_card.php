<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
// 卡片模板，只负责将数据放到对应的位置上
// 无封面时 getArticleCardMedia() 会退回 1x1 透明占位图，据此判断有没有可展示的封面。
// 变量名从 hasImage 改成 hasCoverImage：原名字容易被读成「图片字段非空」，
// 而这里真正的语义是「存在一张能显示的封面」，跟 if 搭配时尤其容易误读。
$hasCoverImage = !isPlaceholderImage($imgUrl);
?>
<article class="article-card">
    <div class="article-card-image">
        <a href="<?= htmlspecialchars($permalink) ?>" title="<?= htmlspecialchars($title) ?>">
            <?php if ($hasCoverImage): ?>
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
