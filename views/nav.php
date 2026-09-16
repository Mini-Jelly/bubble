<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<svg xmlns="http://www.w3.org/2000/svg" class="d-none" aria-hidden="true">
  <symbol id="icon-clean" viewBox="0 0 16 16">
    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
    <path
      d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
  </symbol>
  <symbol id="icon-search" viewBox="0 0 16 16">
    <path
      d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
  </symbol>
  <symbol id="icon-menu" viewBox="0 0 1024 1024">
    <path
      d="M736 352H288a32 32 0 1 1 0-64h448a32 32 0 0 1 0 64z m0 192H288a32 32 0 1 1 0-64h448a32 32 0 0 1 0 64z m0 192H288a32 32 0 0 1 0-64h448a32 32 0 0 1 0 64z">
    </path>
  </symbol>
  <symbol id="icon-setting" viewBox="0 0 1024 1024">
    <path d="M812.6976 195.976533L936.021333 409.6a204.8 204.8 0 0 1 0 204.8L812.714667 828.023467a204.8 204.8 0 0 1-177.373867 102.4H388.676267a204.8 204.8 0 0 1-177.373867-102.4L87.978667 614.4a204.8 204.8 0 0 1 0-204.8l123.323733-213.623467a204.8 204.8 0 0 1 177.373867-102.4h246.647466a204.8 204.8 0 0 1 177.373867 102.4z m-59.118933 34.133334a136.533333 136.533333 0 0 0-118.254934-68.266667H388.676267a136.533333 136.533333 0 0 0-118.254934 68.266667L147.114667 443.733333a136.533333 136.533333 0 0 0 0 136.533334l123.323733 213.623466a136.533333 136.533333 0 0 0 118.254933 68.266667h246.647467a136.533333 136.533333 0 0 0 118.254933-68.266667L876.885333 580.266667a136.533333 136.533333 0 0 0 0-136.533334l-123.323733-213.623466z"></path>
    <path d="M512 682.666667c94.2592 0 170.666667-76.407467 170.666667-170.666667s-76.407467-170.666667-170.666667-170.666667-170.666667 76.407467-170.666667 170.666667 76.407467 170.666667 170.666667 170.666667z m0-68.266667a102.4 102.4 0 1 1 0-204.8 102.4 102.4 0 0 1 0 204.8z"></path>
  </symbol>
</svg>
<nav id="nav" class="nav">
  <!-- 手机端侧边栏按钮 -->
  <button id="sidebarOutBtn" type="button" aria-label="打开侧边栏" aria-controls="sidebar" aria-expanded="false">
    <svg width="36" height="36" fill="#ffffff" aria-hidden="true">
      <use href="#icon-menu"></use>
    </svg>
  </button>
  <!-- LOGO -->
  <div class="nav-logo">
    <a href="<?= $this->options->siteUrl(); ?>">
      <!-- alt 应当是描述性文本；填成 URL 对屏幕阅读器和 SEO 都毫无意义 -->
      <img src="<?= $this->options->themeUrl('assets/img/logo/logo.png'); ?>"
        alt="<?= htmlspecialchars($this->options->title, ENT_QUOTES, 'UTF-8'); ?>">
    </a>
  </div>
  <!-- 搜索框 -->
  <form class="nav-form" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
    <button id="nav-form-submit" class="nav-form-submit" aria-label="Submit" type="submit">
      <svg width="16" height="16" fill="#fff">
        <use href="#icon-search"></use>
      </svg>
    </button>
    <input name="s" class="nav-form-input" id="nav-form-input" placeholder="搜索" autocomplete="off" type="text" aria-label="Search">
    <button id="nav-form-clean" class="nav-form-clean" type="button" aria-label="Clear">
      <svg width="16" height="16" fill="#fff">
        <use href="#icon-clean"></use>
      </svg>
    </button>
  </form>
  <!-- 分类 -->
  <div class="nav-category">
    <?php $this->widget('Widget_Metas_Category_List')->listCategories('wrapClass=category-list'); ?>
  </div>
  <?php
  // 「实验性功能」根据主题设置是否展示近期更新
  // 老用户升级后该配置项为 NULL，因此不能直接和 'on' 严格比较
  if (($this->options->recentUpdatePosts ?? 'off') === 'on') {
    // 通过页面列表反查真实 permalink：写死 index.php/xxx.html 在伪静态站点下会 404
    $recentUpdateUrl = '';
    $this->widget('Widget_Contents_Page_List@nav-recent-pages')->to($navPages);
    while ($navPages->next()) {
      if ($navPages->slug === 'recent-modified') {
        $recentUpdateUrl = $navPages->permalink;
        break;
      }
    }
    if ($recentUpdateUrl !== ''):
  ?>
    <!-- 近期更新：外层必须是列表容器，裸 <li> 放在 <nav> 里是非法的 -->
    <ul class="recent-updates-list">
      <li class="recent-updates">
        <a href="<?= htmlspecialchars($recentUpdateUrl, ENT_QUOTES, 'UTF-8') ?>">近期更新</a>
      </li>
    </ul>
  <?php endif;
  }
  ?>
  <?php
  // 全局设置已迁到右下角悬浮按钮组（见 views/fab.php）。
  // SVG 图标符号仍留在本文件：nav 在 body 里最先渲染，fab.php 引用 #icon-setting 时
  // 才不会踩到「符号尚未定义」。
  ?>
</nav>
<!-- 手机端侧边栏 -->
<div id="sidebar" class="sidebar" data-lenis-prevent>
  <div class="sidebar-header">
    <form id="search" method="post" action="<?= $this->options->siteUrl(); ?>" role="search">
      <input type="text" id="s" name="s" class="text" placeholder="输入关键字搜索" />
      <button type="submit" class="submit">Search</button>
    </form>
  </div>
  <div class="sidebar-content">
    <?php $this->widget('Widget_Metas_Category_List')->listCategories('wrapClass=sidebar-list'); ?>
    <ul class="sidebar-list">
      <li class="category-parent">
        <a href="#">独立页面</a>
        <ul class="sidebar-list">
          <!-- <a> 上不存在 alt 属性，描述性文本应该放在 title 上 -->
          <?php $pages = Typecho_Widget::widget('Widget_Contents_Page_List')->to($page);
          while ($page->next()): ?>
            <li>
              <a href="<?= htmlspecialchars($pages->permalink, ENT_QUOTES, 'UTF-8') ?>"
                title="<?= htmlspecialchars($pages->title, ENT_QUOTES, 'UTF-8') ?>"><?php $pages->title(); ?></a>
            </li>
          <?php endwhile; ?>
        </ul>
      </li>
    </ul>
    <ul class="sidebar-list">
      <li class="category-parent">
        <a href="#">归档</a>
        <ul class="sidebar-list">
          <?php \Widget\Contents\Post\Date::alloc('type=year&format=Y')
            ->parse('<li><a href="{permalink}">{date}年</a></li>'); ?>
        </ul>
      </li>
    </ul>
  </div>
</div>
<!-- 背景蒙版 -->
<div id="sidebar-backdrop"></div>