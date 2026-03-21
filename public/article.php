<?php
// 文章置顶功能实现，在输出文章之前先实现，就是将文章放在第一位以实现置顶
if ($this->is('index') && $this->currentPage === 1 && $this->options->stickyCid) {
    $sticky_arr = array_map('trim', explode("||", $this->options->stickyCid));
    foreach ($sticky_arr as $item) {
        //构建标准对象类
        $object = new stdClass();
        $this->widget('Widget_Archive@' . $item, 'pageSize=1&type=post', 'cid=' . $item)->to($object);
        // 检查文章是否存在
        if ($object->cid) { 
            // 分类
            $categories = $object->categories;
            $categoryData = array();
            foreach ($categories as $cat) {
                $categoryData[] = array(
                    'name' => $cat['name'],
                    'link' => $cat['permalink']
                );
            }
            // 构建数据
            $articleData = [
                'imgUrl'     => getThumbnailLink($object),
                'title'      => '[置顶]' . $object->title,
                'excerpt'    => getArticleExcerpt($object, 120, '...'),
                'category'   => $categoryData,
                'time'       => $object->date->format('Y-m-d'),
                'permalink'  => $object->permalink,
            ];
            //数据构建和渲染分离
            echo renderArticleCard($articleData);
        } else {
            echo "置顶文章不存在~";
        }
    }
}
//文章数据构建
while ($this->next()):
    // 分类数组
    $categoryData = array();
    foreach ($this->categories as $item) {
        $categoryData[] = array(
            'name' => $item['name'],
            'link' => $item['permalink']
        );
    }

    // 构建数据
    $articleData = [
        'imgUrl'     => getThumbnailLink($this),
        'title'      => $this->title,
        'excerpt'    => getArticleExcerpt($this, 120, '...'),
        'category'   => $categoryData,
        'time'       => $this->date->format('Y-m-d'),
        'permalink'  => $this->permalink,
    ];
    //数据构建和渲染分离
    echo renderArticleCard($articleData);
endwhile;
