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
  // 选项表来自 bubbleThemeColors()：前台「个性化设置」面板会按同一份名单渲染色块，
  // 两处各写一遍迟早会漂移（新增一个颜色时漏改其中一处）。
  $themeColor = new Typecho_Widget_Helper_Form_Element_Select(
    'themeColor',
    bubbleThemeColors(),
    'blue',
    '主题颜色',
    '介绍：主题的默认颜色。访客可在右下角「个性化设置」里自行更换，访客的选择优先级更高。'
  );
  $themeColor->setAttribute('class', 'bubble-option Global');
  $form->addInput($themeColor);

  /* 字体来源 */
  $fontFamily = new Typecho_Widget_Helper_Form_Element_Select(
    'fontFamily',
    array(
      'system' => '系统字体',
      'site' => '站点字体',
    ),
    'system',
    '字体来源',
    '介绍：默认使用访客设备自带的系统字体，不下载任何字体文件。<br />
    选择「站点字体」则使用主题内置的 HarmonyOS Sans SC 子集，中文页面需额外下载约 460KB。<br />
    访客可在右下角「个性化设置」里自行更换，访客的选择优先级更高。'
  );
  $fontFamily->setAttribute('class', 'bubble-option Global');
  $form->addInput($fontFamily);

  /* 链接打开方式：站内 / 站外各自可配 */
  $internalLinkTarget = new Typecho_Widget_Helper_Form_Element_Select(
    'internalLinkTarget',
    array(
      '_self' => '当前窗口（默认）',
      '_blank' => '新窗口',
    ),
    '_self',
    '站内链接打开方式',
    '介绍：本站链接（文章、分类、分页、导航等）的打开方式。<br />
    选择「新窗口」会让每次站内跳转都开新标签页，主题的跨文档转场同时失效（转场只在当前文档内生效），请谨慎选择。'
  );
  $internalLinkTarget->setAttribute('class', 'bubble-option Global');
  $form->addInput($internalLinkTarget);

  $externalLinkTarget = new Typecho_Widget_Helper_Form_Element_Select(
    'externalLinkTarget',
    array(
      '_self' => '当前窗口（默认）',
      '_blank' => '新窗口',
    ),
    '_self',
    '站外链接打开方式',
    '介绍：其他域名的链接的打开方式。<br />
    选择「新窗口」时，新标签页会自动补上 rel="noopener noreferrer"，避免新页面通过 window.opener 反向操作本页。'
  );
  $externalLinkTarget->setAttribute('class', 'bubble-option Global');
  $form->addInput($externalLinkTarget);

  /* 回到顶部按钮 */
  $backToTop = new Typecho_Widget_Helper_Form_Element_Select(
    'backToTop',
    array(
      'on' => '显示',
      'off' => '隐藏',
    ),
    'on',
    '回到顶部按钮',
    '介绍：滚动超过一屏后，在右下角显示一个回到顶部的按钮'
  );
  $backToTop->setAttribute('class', 'bubble-option Global');
  $form->addInput($backToTop);

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