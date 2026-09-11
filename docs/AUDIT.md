# Bubble 主题代码审查报告 · 修复台账

审查范围：`functions.php`、`core/*`、`*.php`、`public/*`、`template/*`、`src/**`、`webpack/*`、`dist/*`  
初次审查：2026-09-10  
修复复核：2026-09-10（Typecho 1.2.1 / PHP 7.4.33 / MySQL，本地 `http://localhost/`，未开伪静态）

> 说明：本文件不属于主题包，发布前请删除或加入 `.gitignore`（已加入）。

## 状态图例

| 标记 | 含义 |
| --- | --- |
| ✅ | 已修复并实测复验 |
| 🟡 | 部分解决 / 有意保留，已记录理由 |
| ⬜ | 未处理，需拍板 |
| ❌ | 原结论有误，实测为误报 |

---

## P0 · 必修

| # | 位置 | 问题 | 状态 | 复核说明 |
| --- | --- | --- | --- | --- |
| 1 | `src/scss/_color.scss` | 首屏颜色变量未定义，必然 FOUC | ✅ | `header.php` 内联阻塞脚本一次写全 `theme` / `color-scheme` / `font-size-mode`（含 localStorage 兜底）；`_color.scss` 补 `@media (prefers-color-scheme: …) :root:not([color-scheme])` 无 JS 兜底。实测 `:root[color-scheme=light/dark]` 两套变量完整无缺 |
| 2 | `template/article_card.php` | 卡片懒加载失效（`src` 与 `data-src` 同值） | ✅ | 改为原生 `loading="lazy" decoding="async"` + `width/height` 预留（防 CLS）；已彻底移除 lazysizes（全项目 0 引用），少一个阻塞脚本 |
| 3 | `src/entry/main.js` | Spotlight 灯箱缺 CSS | ✅ | 拆出独立 chunk，命中 `a.spotlight` 时同时 `import` JS 与 `spotlight.min.css`；`dist/spotlight.min.css` 10.7 KiB 已单独产出 |
| 4 | `src/js/main/dark_mode.js` | 死代码且逻辑冲突 | ✅ | 文件已删除 |
| 5 | `core/config.php` `postToc` | 配置项定义但从未读取 | ✅ | `post.php` / `page.php` 用 `$tocEnabled = ($this->options->postToc ?? 'on') === 'on'` 控制按钮；`post_toc.js` 增加 `getById('tocToggleBtn')` 空值保护，配置关闭时不再初始化 |
| 6 | `public/header.php` | 缺 `<head>` 开标签 | ✅ | 结构补全，`lang` 改为 `zh-CN` |
| 7 | `archive.php` | 输出两个 `</body>` | ✅ | `archive.php` / `index.php` / `post.php` / `page.php` 多余的 `</body>` 已删，统一由 `footer.php` 收尾 |
| 8 | `post.php` / `page.php` | 标题未转义拼进属性（XSS） | ✅ | 抽出 `wrapContentImages($content, $fallbackAlt)`：URL 与 alt 均走 `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`；且保留图片自带 alt，仅在缺失时回退标题（避免全篇同 alt 的 SEO 反优化） |

---

## P1 · 性能与正确性

| # | 位置 | 问题 | 状态 | 复核说明 |
| --- | --- | --- | --- | --- |
| 9 | Prism 全量进主包 | 73 KB 全站加载 | ✅ | 改为 `document.querySelector("pre code, code[class*='language-']")` 命中才 `import()`。**`main.min.js` 从 102 KB → 11.5 KiB** |
| 10 | `public/include.php` | JS 同步阻塞、无 defer | ✅ | `main.min.js` / `swiper.min.js` 均加 `defer`（内部逻辑全绑 DOMContentLoaded） |
| 11 | `public/include.php` | Swiper 资源全站加载 | ✅ | 收窄为 `$this->is('index') && $this->getCurrentPage() === 1 && $this->options->swiper`，非首页/非第一页不再下载 94.9 KiB |
| 12 | `public/article.php` | 首页每篇正文处理两次 | ✅ | 合并为 `getArticleCardMedia($post, $len, $append)`，封面+摘要一次取；新增 `getPostContent($post)` 按 cid 做请求内静态缓存。自定义字段齐备时正文完全不解析 |
| 13 | `page_recent_modified.php` | N+1 查询 + 重复 COUNT | 🟡 | 重复 COUNT 已合并（同一套 WHERE 复用到计数）。逐条构造 `Widget_Archive` **有意保留**：`Widget_Archive::execute()` 把排序硬编码为 `created DESC`，不支持「按 modified 排序」，改 JOIN 需自建查询与渲染，风险大于收益（该页为低频页） |
| 14 | `page_recent_modified.php` | 定时发布文章泄露 | ✅ | 筛选条件抽为闭包，补 `created <= now`（原仅 `status='publish'`） |
| 15 | `src/scss/public/_article_card.scss` | 移动端封面隐藏但照样下载 | ⬜ | 现状：≤576px `display:none`。已换原生懒加载，首屏外图片不再下载；但首屏第一张封面在移动端是否仍下载取决于浏览器对无布局盒元素的处理，无法保证。彻底解决需二选一：①`<picture><source media="(min-width:577px)">` + 透明兜底图（保留 `<img>`/alt）；②封面改 CSS `background-image`（移动端零请求，但丢失 `<img>` 与图片搜索）。**属 UI/SEO 取舍，留待拍板** |
| 16 | `src/scss/main.scss` | 三个 `@font-face` 描述符全同，463 KB 白放 | ✅ | 保持同 family、`font-weight:400`，改用 `unicode-range` 做子集切分（实测：us117=U+21-7E 等 107 字形；1900_1=U+4E00-707F；1600_2=U+7089-9F9F），三者互补而非互相覆盖 |
| 17 | `public/article.php` | 置顶文章未去重 | ✅ | 循环内 `in_array((int)$this->cid, $stickyCidList, true)` 跳过重复；置顶改用 `Widget_Archive@sticky-{cid}` + `pageSize=1&type=post` 精确取单篇；`stickyCid` 兼容 `\|\|` / `\|` / `,` 三种分隔 |
| 18 | `dist/` 被 gitignore | clone 后无 CSS/JS | 🟡 | 已在 `README.md` 显著位置写明「发布前必须 `npm i && npm run build`」；`dist/` 仍不入库（体积与产物一致性考量）。另 `.gitignore` 补 `/dist.bak/`、`/.audit-tmp/`、`/AUDIT.md` |

---

## P2 · 代码质量 / SEO / 可访问性

| # | 位置 | 问题 | 状态 | 复核说明 |
| --- | --- | --- | --- | --- |
| 19 | `core/func.php` | 模板不存在时缓冲区泄漏 | ✅ | `renderArticleCard()` 用 `try/catch (Throwable)` 包裹并保证 `ob_end_clean()`；模板缺失改为 `error_log` + 返回 HTML 注释（不再回显服务器路径） |
| 20 | `core/func.php` | `while (ob_get_level() > …)` 死代码 | ✅ | 已移除 |
| 21 | `core/func.php` | `extract($data)` 易覆盖变量 | ✅ | 改为显式解构 + `is_file()` 前置检查 |
| 22 | `core/func.php` | 天数计算绕路且当天显示「超过 0 天」 | ✅ | 改为 `strtotime('today')` 归一再相减、`max(0, round())`；文案对当天单独输出「（今天）」 |
| 23 | `public/footer.php` | 页脚外泄耗时/内存 | ✅ | 改为受 `showPageUsage`（新增，默认关）控制。实测线上页脚输出中「页面生成时间」出现 0 次 |
| 24 | `public/swiper.php` | `explode("\r\n")` 只认 CRLF | ✅ | 改 `preg_split('/\r\n|\r|\n/', …)` |
| 25 | `public/swiper.php` | `list()` 解构格式非法时 Undefined offset | ❌→✅ | 原报告基于旧文件；现实现用 `$parts[$i] ?? ''` 安全取值，图片为空的行直接丢弃。**属误报（已被既有提交修复）** |
| 26 | `public/swiper.php` | `srcset` 误用 + `alt="Loading..."` | ❌→✅ | 现实现 `src` 即真实图，首张 `fetchpriority="high"`、其余 `loading="lazy"`，`alt` 取配置标题，并补 `width/height`。**属误报（同上）** |
| 27 | `public/header.php` | `<base target="_blank">` 污染站内链接 | ✅ | 改为在 `<html>` 写 `open-new-window="on\|off"`，新增 `src/js/main/external_links.js` 仅对**站外**链接补 `target="_blank"` + `rel="noopener noreferrer"`；站内锚点/分页/`href="#"` 不受影响 |
| 28 | `template/article_card.php` | 卡片标题用 `<h1>` | ✅ | 改 `<h2>`。实测首页 `<h1>` 1 个 / `<h2>` 5 个 |
| 29 | `public/post_meta.php` | microdata 孤立无效、schema 用 http | ✅ | 已移除孤立的 `itemprop`；结构化数据统一由 `header.php` 输出一份完整 JSON-LD `BlogPosting`（headline/description/datePublished/dateModified/author/image），保留语义化 `<time datetime>` 与 `rel="author"` |
| 30 | `public/header.php` | description / keywords 全站雷同 | ✅ | 文章页描述改取该文摘要（`getArticleCardMedia`，只解析一次正文） |
| 31 | `public/header.php` | 缺 canonical / og / twitter / theme-color / JSON-LD | ✅ | 全部补齐。`<link rel="canonical">`、`og:type/url/site_name/title/description/image`、`article:published_time/modified_time`、`twitter:card/title/description/image`、明暗两套 `theme-color`、JSON-LD（`JSON_HEX_*` 安全嵌 `<script>`）。canonical 详见文末「新增发现 1」 |
| 32 | `nav.php` / `page_recent_modified.php` | 硬编码 `index.php/recent-modified.html` | ✅ | 两处均改为反查页面列表 `slug === 'recent-modified'` 取 permalink，伪静态下同样正确 |
| 33 | `src/scss/public/_comment.scss` | 硬编码色值，暗色下评论区不可读 | ✅ | 全部替换为语义变量：`--border/--bg-hover/--card1/--comment-author-bg/--text1/--text2/--link`；新增 `--comment-author-bg`（亮 `#fff9e8` / 暗 `#3f3a2b`）。实测编译后 `.comment-author{color:var(--text1)}` 生效 |
| 34 | `src/js/main/settings.js` | 用 value 反查 key，可删 8 行 | ✅ | 改用 `listItem.dataset.settingKey`，已删除反查逻辑 |
| 35 | `src/js/main/nav_sidebar.js` | 展开写死 px、`transitionend` 后不恢复 auto | ✅ | 重写：收起前先写 `scrollHeight` → 触发回流 → 清空；`transitionend` 把 `height` 归还 `auto` |
| 36 | `src/js/main/nav_sidebar.js` | backdrop 反复绑定、无 Esc、无焦点管理 | ✅ | backdrop 只绑一次；补 `aria-expanded` 同步、Esc 关闭、关闭后焦点归还 |
| 37 | `src/js/main/post_toc.js` | `mousemove/mouseup` 永不移除 | ✅ | 改为拖拽期间挂载、`mouseup` 立即解绑；并修 `targetElement` 先取值后判空的空引用 |
| 38 | `src/js/main/post_toc.js` | 注释「1%~6%」与实现不符 | ❌ | 实测：`rootMargin: '-1% 0px -94% 0px'` 把观察带收在视口 1%~6% 高度区间，**注释与实现一致，属误报** |
| 39 | `src/js/main/nav.js` | `isClearBtnVisible` 赋值后从未读取 | ✅ | 死变量已清除 |
| 40 | `public/nav.php` | 硬编码假「友情链接」、`<a>` 上用 `alt` | ✅ | 假数据块已删除 |
| 41 | `public/nav.php` | 裸 `<li>` 出现在 `<div>` 内 | ✅ | 「近期更新」改为 `<ul class="recent-updates-list">` 完整包裹 |
| 42 | `public/nav.php` | `#icon-setting` 用嵌套 `<svg>` | ✅ | 改 `<symbol id="icon-setting">`，根 `<svg>` 补 `aria-hidden` |
| 43 | `public/post_copyright.php` | 0 字节空文件反复 include | ✅ | 已删除，`post.php` / `page.php` 不再引用 |
| 44 | `post.php` / `page.php` | 容器带 `line-numbers` 但未加载行号插件 | ❌ | 实测 `src/js/lib/prism.min.js` 内已含行号插件，插件通过 `Prism.util.isActive(i, 'line-numbers')` **向上遍历祖先**，因此类挂在 `div.post` 上也能识别，并会把类自动搬到 `<pre>`。**属误报**。遗留观察见文末「新增发现 4」 |
| 45 | `archive.php` | 搜索无结果时两个 `<h1>` | ✅ | 空态改 `<h2 class="archive-empty">` |
| 46 | `public/nav.php` | logo `alt` 填的是站点 URL | ✅ | 改为 `$this->options->title` |
| 47 | `nav.php` / `swiper.php` / `tag_cloud.php` / `include.php` 等 | 缺 `__TYPECHO_ROOT_DIR__` 守卫 | ✅ | 全部 `public/*.php` 与 `template/*.php` 已补守卫 |
| 48 | `webpack/*.js` | `terser-webpack-plugin` 声明后未使用 | ✅ | 已移除无用 require |
| 49 | `webpack/webpack.dev.js` | `watch` 恒为 false 的死配置 | ✅ | 已移除 |
| 50 | `package.json` | 有 `browserslist` 但无 babel-loader | 🟡 | 结论修正：`browserslist` 确实被 postcss 链路（autoprefixer / preset-env）使用，**对 CSS 生效**；仅 JS 不降级。目标 `last 2 version / > 1% / not dead` 与主题实际语法基线（ES2017+、无 IE）一致，故未引入 babel。若需支持老旧内嵌浏览器再补 `babel-loader` |
| 51 | `src/scss/_grid.scss` | 生成大量未使用栅格类 | ⬜ | 实测：模板只用到 `col-12` / `col-lg-10` / `offset-lg-1` 共 3 个类，而编译产物含 **96 条栅格规则、3,635 B（占 `main.min.css` 12%，gzip 后约 0.9 KB）**。JS 无动态拼类，裁剪安全；但会牺牲后续扩展便利，**留待拍板** |
| 52 | `src/scss/_post.scss` | 连续 `!important` 反制 | ✅ | 根因（`.post a` 选择器过宽）已拆为 `.post-content a{color:var(--link)}`，`!important` 链已清除；剩余 `!important` 均为响应式覆盖（`.nav` 断点、swiper 主题色），属合理用法 |
| 53 | 全场 | 新增配置项在老用户升级后为 NULL，`=== 'on'` 静默失效 | ✅ | 统一 `?? 'on'` / `?? 'off'` 兜底：`postToc`、`tagCloud`、`recentUpdatePosts`、`showPageUsage`、`openInNewWindow` |
| 54 | `core/config.php` | 轮播图默认值写死作者域名与相对路径 | ✅ | 改用 `Helper::options()->themeUrl(...)` / `siteUrl()` 生成完整 URL，移除 `jjj8.top` 与相对路径 |

---

## 新增发现（本轮复核新定位，原报告未覆盖）

### 1. `$this->header()` 从未被调用 → 评论功能整体失效（P0）

`public/header.php` 原实现完全没调用 Typecho 内置的 `$this->header()`。后果有两个：

1. **评论提交必然失败**：站点开启反垃圾保护（本机 `commentsAntiSpam=1`）时，提交校验走 `Widget\Security::protect()`，它比对 `request->get('_')` 与 `getToken(referer)`。而这个 `_` 隐藏字段**并非静态 HTML**，是 `$this->header()` 输出的内联脚本在 `DOMContentLoaded` 时注入到 `#{$this->respondId}` 内第一个 `<form>` 的。缺了 `header()` 就没有注入脚本，提交时 `_` 为空 → `goBack()` 直接把请求退回。
2. **「回复 / 取消回复」点击报错**：`window.TypechoComment` 同样由 `header()` 输出，缺失时 `reply()` 调用即抛错。

修复：`public/header.php` 补 `$this->header('description=&keywords=')`（传空值避免与自绘的 `description`/`keywords` 重复）。

实测（`/index.php/archives/86/`）：内联脚本含 `input.name = '_'` ✅、含 `window.TypechoComment = {` ✅、`#respond-post-86` 与脚本内 `getElementById('respond-post-86')` 匹配 ✅、`description`/`keywords` 各仅 1 处无重复 ✅。

### 2. `getArchivePermalink()` 的裸属性陷阱（P0 · 已修）

canonical 需重建归档页地址（列表页的 `$this->permalink` 会被 `push()` 进来的文章行覆盖，返回的是「最后一篇文章」的地址）。首版实现读 `$archive->options->index` 与 `$archive->currentPage`，实测输出退化成**相对路径**：

```
/index.php/page/2/        →  "/"        （应为 /index.php/page/2/）
/index.php/2026/page/2/   →  "/2026/"   （应为 /index.php/2026/page/2/）
/index.php/category/小工具/ → "/category/%E5%B0%8F%E5%B7%A5%E5%85%B7/"
```

根因：`Widget\Archive::$options` 是 **protected**、`$currentPage` 是 **private**。模板里 `$this->options` 之所以可用，是因为 `need()` 定义在 `Widget\Archive` 内，被 include 的模板继承了该类作用域；一旦离开类作用域（传进全局函数），读取会落到 `Widget::__get()`，而它只认 `row` 键、`___xxx()` 魔术方法、插件钩子 —— 两者都不满足，**静默返回 null**。于是 `Router::url()` 的 prefix 为空，且 `(int) null === 0` 让 `page > 1` 判断整段失效。

修复：只用 public 方法 —— `getArchiveType()` / `getPageRow()` / `getCurrentPage()` / `getArchiveUrl()`，prefix 改用 `Helper::options()->index`，分页路由按归档类型精确映射（日期归档再按 年/月/日 三档细分）。同类隐患在 `header.php`、`index.php`、`public/article.php`、`public/include.php` 一并改用 `getCurrentPage()`。

实测（全部为绝对地址且自指向）：

| 请求 | canonical |
| --- | --- |
| `/` | `http://localhost/` |
| `/index.php/page/2/` | `http://localhost/index.php/page/2/` |
| `/index.php/2026/` `/2026/02/` `/2026/02/23/` | 各自自指向 |
| `/index.php/2026/page/2/` | `http://localhost/index.php/2026/page/2/` |
| `/index.php/category/小工具/` | 自指向（保留 URL 编码） |
| `/index.php/author/1/` | 自指向 |
| `/index.php/search/字体/` | 自指向 |
| `/index.php/archives/86/` `/index.php/about.html` | 自指向 |

### 3. 亮色模式下 `--link` 未定义，正文链接丢失主题色（P0 · 已修）

`src/scss/_color.scss` 把 `--link` 只写在 `palette-dark` 里，`palette-light` 缺失。`var()` 取不到值时整条 `color` 声明失效（invalid at computed-value time），于是 `.post-content a` / `.post-tags a` / `.comment-form a` / `.article-card-info-footer a` / `.footer a` 在**亮色模式下全部退化成继承色**。已补 `--link: var(--theme-9)`。

顺带做了一次全量变量审计（扫描编译产物）：亮/暗两个 palette 现已完全配对（无 light-only / dark-only 变量）。

### 4. `--tertiaryText` 是拼写错误，`blockquote` 颜色失效（P2 · 未改）

`src/scss/main.scss:106` `blockquote { color: var(--tertiaryText) }` —— 该变量全项目从未定义（应为本主题的 `--text3`），所以这条声明一直无效，引用块文字实际继承正文色。**未改动**：改成 `var(--text3)` 会让引用块文字明显变浅灰，属可见的视觉变更，留待拍板。

### 5. 已发布内容里没有带语言标记的代码块（观察项）

扫描全部已发布文章（cid 75~86）：渲染结果中的代码块都是无属性的 `<pre>`，没有任何 `<code class="language-*">`。而按需加载 Prism 的触发条件是 `pre code, code[class*='language-']` —— 因此**当前线上 Prism 实际不会加载**。不是代码缺陷，但若希望代码块有高亮/行号，需在写作时给围栏标注语言（```` ```php ````）。另外 Typecho 输出的结构是 `<pre><code class="language-x">`，而 Prism 官方样式（含行号、工具栏、复制按钮）以 `pre[class*="language-"]` 为前提，`<pre>` 上没有类会让这部分样式不生效 —— 真要启用高亮时，建议在 `core/func.php` 加一个把 `language-*` 从 `<code>` 复制到 `<pre>` 的规范化函数。

---

## 其它复核结论

- **`.gitignore`**：已补 `/dist.bak/`、`/.audit-tmp/`、`/AUDIT.md`。
- **本轮全过程**：14 条代表性路由（首页 / 分页 / 年·月·日归档 / 分类 / 作者 / 搜索 / 文章 / 独立页 / 近更页 / 标签 / 404）全部返回预期状态码，渲染结果中 `Warning` / `Notice` / `Fatal error` / `Deprecated` 标记均为 **0**。
- **构建产物**：`main.min.js` 11.5 KiB、`main.min.css` 29.5 KiB；`prism` / `spotlight` / `swiper` 均为独立按需 chunk。

## 遗留待拍板

1. **#15** 移动端封面是否彻底停下载（`<picture>` 方案 vs CSS 背景图方案）。
2. **#51** 是否裁剪 `_grid.scss` 未使用栅格（省 3.6 KB / gzip 0.9 KB）。
3. **`--tertiaryText`** 是否改为 `var(--text3)`（会让引用块文字变浅）。
4. **#13** 近更页 N+1 是否值得为自定义排序重写查询。
5. **`dist.bak/`** 已有备份目录，确认无误后可删除（未擅自处理）。
