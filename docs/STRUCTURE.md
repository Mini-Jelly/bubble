# 目录结构说明

一句话规则：**Typecho 的路由文件必须留在根层，其余文件按「运行时 / 构建时 / 文档」三分。**

## 一张表看懂每个目录

| 路径 | 装的是什么 | 什么时候被用到 |
| --- | --- | --- |
| `index.php` `post.php` `archive.php` `page.php` `page_recent_modified.php` | Typecho 的**路由模板入口**，文件名由框架规定，不能改也不能挪 | 访客每次请求 |
| `functions.php` | 主题钩子入口，只负责 `require` 下面 `core/` 里的文件 | 每次请求（框架自动加载） |
| `core/` | **服务端逻辑**：`func.php` 公共函数、`config.php` 后台设置表单、`fields.php` 自定义表单元素 | 每次请求 |
| `views/` | **服务端视图片段**，由 `$this->need()` 包含进来拼页面（导航、页脚、卡片、评论区…） | 每次请求 |
| `assets/` | **不经构建**、由 PHP 用 `themeUrl()` 直接引用的图片（logo、favicon、轮播默认图） | 每次请求 |
| `src/` | **构建源码**：`scss/` 样式、`js/` 脚本、`fonts/` 字体，全部要经过 webpack | 只在开发/构建时 |
| `dist/` | **构建产物**（CSS / JS / 字体）。被 `.gitignore` 忽略，但**运行时依赖它** | 每次请求 |
| `webpack/` | 构建配置（dev / prod 两份，没有 merge） | 只在构建时 |
| `scripts/` | 工程脚本：版本同步、打包 | 只在发版时 |
| `docs/` | 设计决策与历史审查台账。**进仓库，不进发布包** | 只在维护时 |
| `.workbuddy/memory/` | 跨会话的工作记忆（本地，不入库） | 只在维护时 |

## 三个最容易看错的地方

1. **`views/` 以前叫 `public/`。**
   在 Web 生态里 `public/` 有强约定语义——Laravel、Vite 都用它表示「网站根 / 静态资源出口」，
   里面的文件是**可以按 URL 直接访问的**。但这里装的是必须经 `$this->need()` 包含的模板片段，
   语义正好相反，所以改名。`src/scss/views/` 与之一一对应。

2. **`assets/` 和 `src/fonts/` 看着都是「静态资源」，其实不同类。**
   `assets/` 里的东西不参与构建，PHP 直接给出 URL；
   `src/fonts/` 里的字体被 SCSS 的 `@font-face` 引用，要走 webpack 的 `asset/resource`，
   落到 `dist/` 并带内容哈希。所以字体留在 `src/` 下，没有被合并进 `assets/`。

3. **根层那几个 `.php` 不是「没整理好」，是框架约定。**
   Typecho 按固定文件名分派模板，挪进 `pages/` 之类会直接 404。
   这和 Laravel 的 `routes/`、Next.js 的 `app/` 是同一类「约定优于配置」，属于合法例外。

## 命名约定

- 视图与样式**同名一对**：`views/nav.php` ↔ `src/scss/views/_nav.scss`。
- 视图级样式放 `src/scss/views/`，跨页面共用的基础样式（颜色令牌、网格、正文排版）放 `src/scss/` 顶层。
- z-index 不写字面量，走 `:root` 里的 `--z-*` 令牌（`--z-fab` / `--z-backdrop` / `--z-nav` / `--z-sidebar` / `--z-toc`）。

## 发布包里有什么

由 `scripts/package.js` 的**白名单**决定，只含 Typecho 运行时会读的文件：
路由入口、`functions.php`、`core/`、`views/`、`dist/`、`assets/`、`screenshot.png`、`README.md`、`LICENSE`。

`src/` `webpack/` `scripts/` `docs/` `node_modules/` 以及所有配置文件都不进包——
这样就不会出现「下载主题的用户拿到一堆源码和他的构建缓存」这种事。

```bash
npm run build && npm run package   # → .package/bubble-<版本>.zip
```
