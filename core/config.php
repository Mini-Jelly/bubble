<?php
/* 主题后台配置文件 */
function themeConfig($form)
{
?>
  <link rel="stylesheet" href="<?php Helper::options()->themeUrl('dist/theme_config.min.css') ?>">
  <script src="<?php Helper::options()->themeUrl('dist/theme_config.min.js') ?>"></script>
  <script>
    document.documentElement.setAttribute('theme', '<?php $themeColor = Typecho_Widget::widget('Widget_Options')->themeColor;
                                                    echo ($themeColor) ?>');
    /* 适配单一模式 */
    document.documentElement.setAttribute('color-scheme', 'light');
  </script>
  <div class="bubble">
    <div class="bubble-aside">
      <div class="logo">Bubble</div>
      <ul class="bubble-aside-list">
        <li class="config-item active" data-target="Global">全局设置</li>
        <li class="config-item" data-target="Home">首页设置</li>
        <li class="config-item" data-target="Article">文章设置</li>
        <li class="config-item" data-target="Other">其他设置</li>
        <li class="config-item" data-target="Dev">开发者选项</li>
      </ul>
    </div>
  <?php
  /* 主题颜色 */
  $themeColor = new Typecho_Widget_Helper_Form_Element_Select(
    'themeColor',
    array(
      'blue' => '蓝色(默认)',
      'red' => '红色',
      'pink' => '粉红色',
      'grape' => '葡萄色',
      'violet' => '紫罗兰色',
      'indigo' => '靛蓝色',
      'cyan' => '蓝绿色',
      'teal' => '鸭绿色',
      'green' => '绿色',
      'lime' => '酸橙绿色',
      'yellow' => '黄色',
      'orange' => '橘色',
    ),
    'blue',
    '主题颜色',
    '介绍：用于修改主题颜色'
  );
  $themeColor->setAttribute('class', 'bubble-option Global');
  $form->addInput($themeColor);

  /* 在新窗口打开 */
  $openInNewWindow = new Typecho_Widget_Helper_Form_Element_Select(
    'openInNewWindow',
    array(
      'off' => '关闭 - 页内跳转',
      'on' => '打开 - 新窗口打开链接',
    ),
    'off',
    '全局新窗口打开',
    '介绍：选择新窗口是页内跳转的方式或者新窗口打开的方式</br>'
  );
  $openInNewWindow->setAttribute('class', 'bubble-option Global');
  $form->addInput($openInNewWindow);

  /* 轮播图 */
  // ⚠️ Widget\Options::themeUrl() 与 siteUrl() 是「输出」方法，内部是 echo 而不是 return。
  //    在赋值上下文里调用它们，会把 URL 直接打印到设置页面（表现为设置页面上多出一串
  //    拼在一起的网址），同时变量拿到 null。取值必须用「魔术属性」形式 ——
  //    属性访问会走到 ___themeUrl() 的 return 分支，才是真正的取值方式。
  //    完整 URL 是必要的：相对路径留到模板里按站点根补全，避免安装目录/伪静态差异导致的 404。
  $swiperThemeUrl = Helper::options()->themeUrl;
  $swiperSiteUrl  = Helper::options()->siteUrl;
  $swiperDefaultImage1 = Typecho_Common::url('assets/img/swiper/s1.png', $swiperThemeUrl);
  $swiperDefaultImage2 = Typecho_Common::url('assets/img/swiper/s2.png', $swiperThemeUrl);
  $swiper = new Typecho_Widget_Helper_Form_Element_Textarea(
    'swiper',
    NULL,
    $swiperDefaultImage1 . '||' . $swiperSiteUrl . '||广告招租位' . "\r\n"
      . $swiperDefaultImage2 . '||' . $swiperSiteUrl . '||广告招租位',
    '轮播图',
    '介绍：用于在首页展示轮播图 <br />
        格式：图片链接 || 跳转链接 || 跳转文字 <br />
        图片建议大小：947 x 250 px <br />
        例如：<br />
        https://baidu.com/img.png || https://baidu.com || 百度一下 <br />
        https://v.qq.com/img.png || https://v.qq.com || 腾讯视频'
  );
  $swiper->setAttribute('class', 'bubble-option Home');
  $form->addInput($swiper);

  /* 标签云 */
  $tagCloud = new Typecho_Widget_Helper_Form_Element_Select(
    'tagCloud',
    array(
      'on' => '开启（默认）',
      'off' => '关闭'
    ),
    'on',
    '标签云',
    '介绍：用于在首页显示标签云'
  );
  $tagCloud->setAttribute('class', 'bubble-option Home');
  $form->addInput($tagCloud);

  /* 文章首页置顶功能 */
  $stickyCid = new Typecho_Widget_Helper_Form_Element_Text(
    'stickyCid',
    NULL,
    NULL,
    '置顶文章',
    '介绍：在这里填入一个文章的cid,在加载文章时会优先加载置顶文章<br />
         格式：数字 || 数字 || 数字 <br />
         例如：1 || 2 || 3'
  );
  $stickyCid->setAttribute('class', 'bubble-option Home');
  $form->addInput($stickyCid);

  /* 文章阅读时目录功能 */
  $postToc = new Typecho_Widget_Helper_Form_Element_Select(
    'postToc',
    array(
      'on' => '开启（默认）',
      'off' => '关闭'
    ),
    'on',
    '文章目录',
    '介绍：用于在文章页面显示目录'
  );
  $postToc->setAttribute('class', 'bubble-option Article');
  $form->addInput($postToc);

  /* ICP备案号 */
  $ICP = new Typecho_Widget_Helper_Form_Element_Text(
    'ICP',
    NULL,
    NULL,
    'ICP备案号',
    '介绍：填写你的ICP备案号，由三部分组成：(省简写)ICP备+主体序列号+网站序列号，如：京ICP备00000000号-*。'
  );
  $ICP->setAttribute('class', 'bubble-option Other');
  $form->addInput($ICP);

  /* 自定义头部代码 */
  $customHeader = new Typecho_Widget_Helper_Form_Element_Textarea(
    'customHeader',
    NULL,
    NULL,
    '自定义头部代码',
    '介绍：用于填写头部代码，例如谷歌analyze和百度统计'
  );
  $customHeader->setAttribute('class', 'bubble-option Other');
  $form->addInput($customHeader);

  /* 自定义底部代码 */
  $customFooter = new Typecho_Widget_Helper_Form_Element_Textarea(
    'customFooter',
    NULL,
    NULL,
    '自定义底部代码',
    '介绍：用于填写底部代码，例如某些插件'
  );
  $customFooter->setAttribute('class', 'bubble-option Other');
  $form->addInput($customFooter);

  /* 开启近期更新文章功能 */
  $recentUpdatePosts = new Typecho_Widget_Helper_Form_Element_Select(
    'recentUpdatePosts',
    array(
      'off' => '关闭（默认）',
      'on' => '开启'
    ),
    'off',
    '近期更新文章',
    '介绍：用于在首页导航栏显示近期修改过的文章的入口<br />
    前提：在后台创建独立页面并命名「recent-modified」，在自定义模板中选择「最近修改的文章」模板，该功能才能正确使用'
  );
  $recentUpdatePosts->setAttribute('class', 'bubble-option Dev');
  $form->addInput($recentUpdatePosts);

  /* 页脚显示页面耗时 / 内存占用 */
  $showPageUsage = new Typecho_Widget_Helper_Form_Element_Select(
    'showPageUsage',
    array(
      'off' => '关闭（默认）',
      'on' => '开启'
    ),
    'off',
    '显示页面耗时',
    '介绍：开启后会在页脚输出页面生成耗时与内存占用，仅用于调试，线上请保持关闭'
  );
  $showPageUsage->setAttribute('class', 'bubble-option Dev');
  $form->addInput($showPageUsage);
} ?>