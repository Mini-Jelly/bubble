# Bubble

一个给 [Typecho](https://typecho.org/) 用的博客主题。演示站点：https://jjj8.top

## 1 主题特点

🔍主题 SEO 极致优化，Lighthouse SEO 跑分彪满 100 分

🚀全站图片懒加载，轻量级设计，极速浏览体验

😎没有一行多余代码，大量的注释，友好的开发体验

✅响应式界面、个性化标徽、自定义首页轮播图(Swiper)，广告大图(Banner)，头部代码，底部代码，文章缩略图设置，文章置顶功能，代码高亮

🎨12种不同的颜色主题和🌙夜间模式

![https://oss.jjj8.top/ztjt.png](https://oss.jjj8.top/ztjt.png)

## 2 如何使用

### 2.1 下载主题文件

通过GitHub页面右边的Releases，你会看到一个版本号，点击即可进入下载页面。

### 2.2 将主题文件解压放进网站目录

Typecho 的主题统一放在 `usr/themes/` 下面。你要让最终路径变成这样：

```
你的网站根目录/
└── usr/
    └── themes/
        └── bubble/          ← 主题文件夹，名字就叫 bubble
            ├── index.php
            ├── core/
            ├── views/
            └── ...
```

### 2.3 在后台启用

登录 Typecho 后台 → 左侧菜单 **控制台 → 外观** → 找到 **bubble** → 点 **启用**。


## 3 常见问题

**Q：怎么换成自己的 Logo？**
替换 `assets/img/logo/logo.png`。favicon 是 `assets/img/favicon/favicon.svg`。Logo 默认是240×96的。

**Q：怎么关闭右下角的悬浮按钮组？**
可以进入主题后台 → 全局设置里把「回到顶部按钮」设为隐藏即可（个性化设置面板跟着一起隐藏）。

## 4 二次开发

### 4.1 环境要求

- Node.js **22+**
- npm
- PHP **7.4+**

### 4.2 上手

```bash
git clone https://github.com/Mini-Jelly/bubble.git  # 使用git克隆代码
cd bubble

npm install        # 安装依赖
npm run dev        # 启动开发服务器，修改 src/ 下文件自动编译更新，实时查看效果
npm run build      # 生产构建，输出到 dist/
```

### 4.3 目录结构

```
根层 *.php    Typecho 路由入口（文件名由框架规定，不能随意修改文件位置）
core/        服务端逻辑：公共函数 / 后台设置表单
views/       服务端视图片段，由 need() 包含
assets/      不经构建的图片，PHP 直接引用
src/         构建源码（scss / js / fonts），要经过 webpack
dist/        构建产物
scripts/     工程脚本（版本同步 / 打包）
```
| 想改什么 | 位置 |
| --- | --- |
| 页面 HTML 结构 | `views/`（导航、页脚、文章卡片、评论区…） |
| 页面样式 | `src/scss/views/` |
| 全局样式 / 颜色变量 / 栅格 | `src/scss/` 顶层 |
| 交互脚本 | `src/js/main/` |
| 后台设置项 | `core/config.php` |
| 公共函数 | `core/func.php` |

**视图和样式是「同名一对」**：`views/nav.php` ↔ `src/scss/views/_nav.scss`。

配色改 `src/scss/_color.scss` 里的两个 mixin（`palette-light` / `palette-dark`），
注意：**新增颜色变量必须亮暗两套都定义**——`var()` 取不到值时整条 CSS 声明会静默失效，不报错、不警告，只是颜色悄悄变回继承色。

### 4.4 打包发布

```bash
npm run build && npm run package
```

打包时会**自动剥离全部注释**，所以发布包是一份没有注释的「成品代码」，行内 HTML 注释也不会再跟着页面传到浏览器。只有 `index.php` 的主题头注释会被保留，Typecho 靠它读取主题名 / 作者 / 版本，剥掉后台主题列表就会变成空的。剥离只发生在打包阶段生成的副本上，仓库里的源码注释原样保留，不影响二次开发。

> 该步骤依赖 PHP 命令行（`php`）。找不到时会直接中断并提示，不会静默发出带注释的包。

### 4.5 版本与发布

版本号遵循[语义化版本](https://semver.org/lang/zh-CN/)，**唯一真源是 `package.json` 的 `version`**。
主题头 `index.php` 里的 `@version` 由脚本自动同步，`@build` 记录构建日期，这两个字段全自动生成无需手改。

| 命令 | 用途 |
| --- | --- |
| `npm run version:sync` | 只把 `package.json` 的版本同步到 `index.php` |
| `npm run release:patch` | 修订号 +1：向下兼容的问题修复 |
| `npm run release:minor` | 次版本号 +1：向下兼容的功能新增 |
| `npm run release:major` | 主版本号 +1：不兼容的改动 |

`release:*` 会依次完成：升版本号 → 同步 `index.php` → 提交 → 打 `vX.Y.Z` 标签。
推送标签即可在 GitHub 上形成发布点：

```bash
git push && git push --tags
```

提交信息遵循 [Conventional Commits](https://www.conventionalcommits.org/zh-hans/)，常用前缀：`feat` 新增、`fix` 修复、`perf` 性能、`refactor` 重构、`docs` 文档、`chore` 杂项。

### 4.6 开发规范（仅适用于本项目）

- 所有文件名用 snake_case（`post_meta.php`、`_tag_cloud.scss`）
- CSS 类名、JS 的 id 用短横线连接（`.article-card`、`#backToTop`）
- PHP 函数名、JS 函数名用小驼峰（`getArticleCardMedia()`、`syncAll()`）
- z-index 不写字面量，统一用 `:root` 里的 `--z-*` 令牌

## 5 赞助

![donate](https://oss.jntm6.eu.org/donate_code.png)

| 赞助名单 | 赞助金额（RMB） |
| -------- | --------------- |
| 😎       | 🌹              |

## 6 反馈以及讨论

1. 你可以在 Issue 里向我反馈问题，我肯定会看，但是不一定会回复
2. Telegram 电报群：[点击加入](https://t.me/+z_4fexVE8klhNjk1)

## 7 LICENSE

[MIT](./LICENSE)
