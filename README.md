# 主题介绍

## 🌲开发主旨

我经常使用Typora写一些笔记或者文章，所以本主题为了适配标准的md文件，无论是将Typora的文章导入typecho还是从typecho导出，都能以同样的形式展示文字，因此编辑器中不会自定义一些其他格式例如进度条或者日历等等，修饰只是额外

## 🌈主题特点

1. 主题 SEO 极致优化，Lighthouse SEO 跑分彪满 100 分
2. 全站图片懒加载，提高加载速度
3. 轻量级设计，极速浏览体验，没有一行多余代码
4. 大量的注释

## 🤏可用性

✅响应式界面、个性化标徽

✅自定义首页轮播图(Swiper)，广告大图(Banner)，头部代码，底部代码

✅文章缩略图设置，📌文章置顶功能

❌支持文章二维码打赏、代码高亮

❌🔥支持公共资源选择CDN加载

🎨12种不同的颜色主题和🌙夜间模式


## 👀演示地址

| 站点 | 状态 | 演示地址 |
| ---- | ---- | -------- |
| ❌   | ❌   | ❌       |

## 🐒反馈以及讨论

1. 你可以在使用Issue向我反馈问题，我肯定会看，但是不一定会回复
2. Telegram电报群：[点击加入](https://t.me/+z_4fexVE8klhNjk1)

## 😮如何使用

> ⚠️ **直接 `git clone` 得到的仓库里没有 `dist/`。**
> 主题运行时依赖 `dist/main.min.css`、`dist/main.min.js` 等构建产物，
> 而 `dist/` 被 `.gitignore` 排除了。克隆后**必须**先执行下面两步，否则页面会完全失去样式：
>
> ```bash
> npm install
> npm run build
> ```
>
> 如果只是想安装使用，请下载右侧的发行版压缩包（已包含 `dist/`），解压到 `usr/themes/` 下即可。

蓝奏云下载压缩包，解压到你的主题theme目录下即可，或者点击右边的发行版下载

## 🎮二次开发

1. 克隆本项目

```
git clone https://github.com/Mini-Jelly/xxxx.git
```

2. 在终端加载项目

```
npm install
```

3. 开始编辑代码

推荐使用vscode作为编码器(你喜欢就好)

4. 构建项目

使用`npm run build`命令构建项目（可以参考package.json）

> 打包发布时必须带上 `dist/` 目录；`dist/` 是运行时依赖，不是可选的缓存。

### 🔖版本与发布

版本号遵循[语义化版本](https://semver.org/lang/zh-CN/)，**唯一真源是 `package.json` 的 `version`**。
主题头 `index.php` 里的 `@version` 由脚本自动同步，`@build` 记录构建日期，
这两个字段都不要手改。

| 命令 | 用途 |
| ---- | ---- |
| `npm run version:sync` | 只把 `package.json` 的版本同步到 `index.php` |
| `npm run release:patch` | 修订号 +1：向下兼容的问题修复 |
| `npm run release:minor` | 次版本号 +1：向下兼容的功能新增 |
| `npm run release:major` | 主版本号 +1：不兼容的改动 |

`release:*` 会依次完成：升版本号 → 同步 `index.php` → 提交 → 打 `vX.Y.Z` 标签。
推送标签即可在 GitHub 上形成发布点：

```
git push && git push --tags
```

发版前请把变更写入 [CHANGELOG.md](./CHANGELOG.md)。

提交信息遵循 [Conventional Commits](https://www.conventionalcommits.org/zh-hans/)，
常用前缀：`feat` 新增、`fix` 修复、`perf` 性能、`refactor` 重构、`docs` 文档、
`chore` 杂项。**一个提交只做一件事**，便于回溯与回滚。

### ✏开发规范（仅适用于本项目）

- 所有文件名应当遵循snake_case下划线命名法。
- CSS类名，JS的id命名使用短横线“-”连接
- php函数名，JS函数名使用小驼峰命名

### 💼文件结构说明

| 文件夹名称 | 文件夹性质 | 说明                                                         |
| :--------: | :--------: | :----------------------------------------------------------- |
|   assets   | 资产文件夹 | 包含**未修改过的**JavaScript库文件(可以通过CDN加载)，主题所使用的图片，字体 |
|    core    |  核心代码  | 有关主题设置的代码，包含主题后台配置、主题使用的函数、主题的自定义字段 |
|    dist    | 输出文件夹 | 源代码文件夹src的代码经过编译压缩后，输出文件到dist文件夹    |
|   public   | 公共文件夹 | 主要包含主题频繁使用的php文件,放在piblic文件夹提高复用率     |
|    src     | 源码文件夹 | 包含主题构建所需的(**未经过修改的**)库文件(包含css文件和JavaScript文件)，用于编译css文件的scss文件夹，及主题使用的JavaScript文件 |
|  template  | 模板文件夹 | 渲染某些内容时使用的模板，用于复用代码                       |
|  webpack   | 打包文件夹 | 打包程序webpack执行打包的代码，执行代码后就能将src文件夹的源代码编译输出到dist文件夹 |

### 📖文件说明

| 文件路径           | 说明                                                         |      |
| ------------------ | ------------------------------------------------------------ | ---- |
| woff2/1600_2.woff2 | 这三个woff2文件都是字体文件                                  |      |
| woff2/1900_1.woff2 | 包含3500个通用汉字以及117个常用字符                          |      |
| woff2/us117.woff2  | 拆成两个是从文件大小考量的。通用汉字[参考链接](https://faculty.blcu.edu.cn/xinghb/zh_CN/article/167473/content/1045.htm) |      |



## 🌹赞助

![donate](https://oss.jntm6.eu.org/donate_code.png)

| 赞助名单 | 赞助金额（RMB） |
| -------- | --------------- |
| 😎       | 🌹              |

## LICENSE

MIT