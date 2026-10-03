//引入css库
import "normalize.css";
// 注意：spotlight 灯箱不在这里静态引入。它只在文章 / 独立页面出现，
// JS 与 CSS 合计约 33KB，改为在文末检测到 .spotlight 时才按需拉取。
// 注意：Prism 不在这里静态引入。它压缩后约 72KB，而绝大多数页面（首页、归档、
// 搜索）没有任何代码块，静态引入会让全站为它买单。改为在文末按需求动态加载。
//引入scss
import "../scss/main.scss";
//引入js
import "../js/main/smooth_scroll.js";
import "../js/main/drawer.js";
import "../js/main/nav.js";
import "../js/main/nav_sidebar.js";
import "../js/main/post_toc.js";
import "../js/main/settings.js";
import "../js/main/footer.js";
import "../js/main/link_targets.js";
import "../js/main/back_to_top.js";

/* 以下资源只在特定页面用得到，按需动态加载，避免全站买单 */
document.addEventListener("DOMContentLoaded", () => {
  // Prism：只有页面里真的存在代码块时才加载
  if (document.querySelector("pre code, code[class*='language-']")) {
    import(/* webpackChunkName: "prism" */ "../js/lib/prism.min.js");
  }

  // Spotlight 灯箱：只有文章 / 独立页面的图片会被包装成 a.spotlight
  if (document.querySelector("a.spotlight")) {
    import(/* webpackChunkName: "spotlight" */ "spotlight.js");
    // spotlight 的样式是独立文件，不引入的话灯箱浮层完全没有样式
    import(/* webpackChunkName: "spotlight" */ "spotlight.js/dist/css/spotlight.min.css");
  }
});