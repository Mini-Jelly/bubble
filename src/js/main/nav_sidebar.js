import { getById } from "./global.js";

document.addEventListener("DOMContentLoaded", () => {
  const sidebarOutBtn = getById("sidebarOutBtn");
  const sidebar = getById("sidebar");
  const backdrop = getById("sidebar-backdrop");

  if (!sidebar || !sidebarOutBtn) return;

  const isOpen = () => sidebar.hasAttribute("open");

  const closeSidebar = () => {
    sidebar.removeAttribute("open");
    sidebarOutBtn.setAttribute("aria-expanded", "false");
  };

  const openSidebar = () => {
    sidebar.setAttribute("open", "");
    sidebarOutBtn.setAttribute("aria-expanded", "true");
  };

  // 蒙版监只听一次即可。原实现每次开关都 add/remove，属于无谓的反复绑定
  if (backdrop) {
    backdrop.addEventListener("click", closeSidebar);
  }

  sidebarOutBtn.addEventListener("click", () => {
    if (isOpen()) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });

  // 键盘用户需要能用 Esc 退出侧边栏，并把焦点还给触发按钮
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && isOpen()) {
      closeSidebar();
      sidebarOutBtn.focus();
    }
  });

  /**
   * 切换子菜单高度
   *
   * 展开时先写死 px 才能有过渡动画；收起时必须「先写死当前高度再清空」，
   * 否则浏览器拿不到起始值，auto → 0 这种不可插值的过渡会直接跳变。
   *
   * @param {HTMLElement} submenu  需要动画的 ul.sidebar-list
   * @param {boolean} shouldOpen   true: 展开, false: 收起
   */
  const toggleSubmenuHeight = (submenu, shouldOpen) => {
    if (!submenu) return;

    if (shouldOpen) {
      submenu.style.height = submenu.scrollHeight + "px";
      return;
    }

    submenu.style.height = submenu.scrollHeight + "px";
    // 读取 offsetHeight 强制浏览器计算一次布局，拿到上面写入的起始高度
    void submenu.offsetHeight;
    submenu.style.height = "";
  };

  /**
   * 展开动画结束后把高度交还给 auto。
   *
   * 停留在写死的 px 上会有两个后遗症：改字号、转屏之后高度不再重算，
   * 子菜单内容被截断；而且长列表一旦换行也会被压住。
   */
  sidebar.addEventListener("transitionend", (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (event.propertyName !== "height") return;

    const parent = target.parentElement;
    if (parent && parent.hasAttribute("open")) {
      target.style.height = "auto";
    }
  });

  // 递归遍历所有符合条件的 li 元素，并为其绑定点击事件
  const addToggleListeners = (allParentItems) => {
    allParentItems.forEach((item) => {
      // 只有在 childElementCount > 1 的情况下，才意味着该菜单有子分类
      if (item.childElementCount > 1) {
        // 拥有子分类的菜单添加图标类
        item.classList.add("toggle-icon");

        // 对拥有子分类的元素添加点击监听事件
        item.addEventListener("click", (event) => {
          // 这里的 scope 表示调用该方法的元素本身，也就是 item
          const submenu = item.querySelector(":scope > ul.sidebar-list");
          const isExpanded = item.hasAttribute("open");

          // 1. 先切换 open 属性（保持原有逻辑，用于 CSS 图标旋转等）
          if (isExpanded) {
            item.removeAttribute("open");
          } else {
            item.setAttribute("open", "");
          }

          // 2. 执行高度动画
          toggleSubmenuHeight(submenu, !isExpanded);

          // 3. 阻止事件冒泡，避免触发父级菜单的点击事件
          event.stopPropagation();
        });
      } else if (item.childElementCount === 1 && item.childNodes.length === 1) {
        // 如果不是一个可以展开的分类，则优化扩大点击范围，因为不需要展开，直接点就跳转即可
        item.childNodes[0].style.display = "block";
      }
    });
  };

  // 获取所有具有 category-parent 类的 <li> 元素
  const parentItems = sidebar.querySelectorAll("ul.sidebar-list > li.category-parent");
  addToggleListeners(parentItems);
});
