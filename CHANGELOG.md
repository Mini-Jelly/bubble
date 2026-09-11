# 更新日志

本项目的所有重要变更都记录在此文件。

格式参考 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.1.0/)，
版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)：

- **主版本号**：不兼容的改动（例如配置项被移除或改名、模板变量变更）
- **次版本号**：向下兼容的功能新增
- **修订号**：向下兼容的问题修复

发布流程见 [README](./README.md#-版本与发布)。

## [未发布]

## [1.1.0] - 2026-09-11

首个带 tag 的正式版本。此前的提交均属于内测阶段，未做版本区分。

### 新增

- 后台「开发者选项」新增「显示页面耗时」开关。页脚的生成耗时与内存占用原先
  始终输出，现改为默认关闭
- 文章页面补充 JSON-LD 结构化数据、`og:image`、`twitter:card` 与 `theme-color`

### 修复

- **评论功能整体不可用**：主题从未调用 `$this->header()`，导致反垃圾 `_` token
  与 `window.TypechoComment` 回复脚本都没有输出，评论必定提交失败
- **`rel=canonical` 指向错误**：列表页会退化成「最后一篇文章」的地址，分页还会
  丢成相对路径
- **轮播图在分页、子路径下图片 404**：配置里填相对地址时会被按当前目录解析
- **后台设置页出现一串拼接的网址**：误把 `themeUrl()` / `siteUrl()` 这类输出型
  方法当成取值方法使用
- **近期更新页会泄露定时发布文章**：查询条件缺少 `created <= now`
- 置顶文章在列表里重复渲染
- 暗色模式下正文链接、评论区多处硬编码颜色导致的可读性问题
- 首屏闪白（FOUC）

### 优化

- 首屏 JS 从 102 KB 降至 11.5 KiB：Prism / Spotlight 拆为按需加载的 chunk，
  Swiper 仅首页加载
- 文章正文在单次请求内按 cid 只解析一次，消除列表页的重复解析
- 全站图片改用原生 `loading="lazy"`，不再依赖 lazysizes
- 文章卡片标题层级由 `h1` 修正为 `h2`，一页只保留一个 `h1`

### 变更

- `<base target="_blank">` 改为 `html[open-new-window]` 属性，由脚本为站外链接
  补 `target` 与 `rel="noopener noreferrer"`
- 版本号与构建日期分离：`@version` 记语义化版本，`@build` 记构建日期
- 引入本文件与 `package.json` 作为版本的唯一真源

## [1.0.0] - 2026-03-21

### 新增

- 内测基线：主题基础功能、12 套配色与夜间模式、响应式布局、轮播图、
  文章目录、标签云、近期更新页

[未发布]: https://github.com/Mini-Jelly/bubble/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/Mini-Jelly/bubble/releases/tag/v1.1.0
