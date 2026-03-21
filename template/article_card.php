<?php
// 卡片模板，只负责将数据放到对应的位置上
?>
<article class="article-card">
    <div class="article-card-image">
        <?php
        // 如果没有图片的话，默认是设置一个data:image的，只需要判断这个就能得知有没有图片
        if (substr($imgUrl, 0, 10) === 'data:image'): ?>
            <div class="no-image"></div>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($permalink) ?>" title="<?= htmlspecialchars($title) ?>">
            <img class="lazyload" src="<?= htmlspecialchars($imgUrl) ?>" data-src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($title) ?>">
        </a>
    </div>

    <div class="article-card-info">
        <header class="article-card-info-header">
            <h1>
                <a href="<?= htmlspecialchars($permalink) ?>"><?= htmlspecialchars($title) ?></a>
            </h1>
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