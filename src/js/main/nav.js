import { getById } from "./global.js";
document.addEventListener("DOMContentLoaded", () => {
  const nav = getById("nav");
  if (!nav) return;

  /* 导航条滚动后才升起阴影：静止时加阴影等于常驻一层灰边，反而脏。
     用 IntersectionObserver 观察一个贴在文档顶部的哨兵，而不是在 scroll 里读
     offsetTop —— 后者每帧都要强制一次同步布局，而且会被平滑滚动库的虚拟滚动绕开。 */
  const sentinel = document.createElement("div");
  sentinel.setAttribute("aria-hidden", "true");
  sentinel.style.cssText =
    "position:absolute;top:0;left:0;width:1px;height:8px;pointer-events:none";
  document.body.prepend(sentinel);

  new IntersectionObserver(
    ([entry]) => nav.classList.toggle("is-scrolled", !entry.isIntersecting),
    { threshold: 0 }
  ).observe(sentinel);

  /* 导航当前项。
     「首页」那一项由 PHP 判定（views/nav.php），首屏就带着指示条、不会闪烁；
     这里只负责分类项 —— 比对路径而不是去读 Typecho 的 widget 上下文，
     一套逻辑同时覆盖分类、标签、归档，也不依赖伪静态开关。
     只标顶级项：下拉里的子项不该出现指示条。 */
  const here = location.pathname.replace(/\/+$/, "") || "/";
  nav
    .querySelectorAll(".nav-category > .nav-link, .nav-category > .category-list > li > a")
    .forEach((link) => {
      let url;
      try {
        url = new URL(link.href, location.href);
      } catch {
        return;
      }
      if (url.origin !== location.origin) return;
      if ((url.pathname.replace(/\/+$/, "") || "/") === here) {
        link.classList.add("is-current");
      }
    });

  const categoryList = nav.querySelectorAll("ul.category-list > li.category-child");
  const searchSubmit = nav.querySelector(".nav-form-submit");
  const inputField = getById("nav-form-input");
  const clearBtn = getById("nav-form-clean");

  // 添加图标
  const addIcon = (categoryList) => {
    categoryList.forEach((item) => {
      if (item.childElementCount > 1) {
        item.classList.add("category-list-icon");
        const nestedItems = item.querySelectorAll("li");
        if (nestedItems.length) {
          addIcon(nestedItems);
        }
      }
    });
  };
  addIcon(categoryList);

  // 搜索框逻辑
  inputField.addEventListener("input", () => {
    clearBtn.style.display = inputField.value.length > 0 ? "inline-block" : "none";
  });

  clearBtn.addEventListener("click", () => {
    inputField.value = "";
    clearBtn.style.display = "none";
  });

  inputField.addEventListener("focus", () => searchSubmit.classList.add("rotate"));
  inputField.addEventListener("blur", () => searchSubmit.classList.remove("rotate"));
});
