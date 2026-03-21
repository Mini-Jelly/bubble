import { getById } from "./global.js";

document.addEventListener("DOMContentLoaded", () => {
  const sidebarOutBtn = getById("sidebarOutBtn");
  const sidebar = getById("sidebar");
  const backdrop = getById("sidebar-backdrop");

  // 定义一个函数，用于移除 sidebar 的 open 属性
  const closeSidebar = () => {
    sidebar.removeAttribute("open");
  };

  // 为按钮添加点击事件
  sidebarOutBtn.addEventListener("click", () => {
    if (!sidebar.hasAttribute("open")) {
      sidebar.setAttribute("open", "");
      backdrop.addEventListener("click", closeSidebar);
    } else {
      closeSidebar();
      backdrop.removeEventListener("click", closeSidebar);
    }
  });

  /**
   * 切换子菜单高度的核心函数
   * @param {HTMLElement} submenu - 需要动画的 ul.sidebar-list 元素
   * @param {boolean} shouldOpen - true: 展开, false: 收起
   */
  const toggleSubmenuHeight = (submenu, shouldOpen) => {
    if (!submenu) return;
   
    if (shouldOpen) {
      // === 展开逻辑 ===
      // 1. 获取内容实际高度
      const scrollHeight = submenu.scrollHeight;
      // 2. 添加style属性
      submenu.style.height = scrollHeight + 'px';
    } else {
      // === 收起逻辑 ===
      submenu.style.height = '';
    }
  };

  // 递归遍历所有符合条件的 li 元素，并为其绑定点击事件
  const addToggleListeners = (allParentItems) => {
    allParentItems.forEach((item) => {
      // 只有在 childElementCount > 1 的情况下，才意味着该菜单有子分类
      if (item.childElementCount > 1) {
        // 拥有子分类的菜单添加图标类
        item.classList.add("toggle-icon");
        
        // 对拥有子分类的元素添加点击监听事件
        item.addEventListener("click", (event) => {
          const submenu = item.querySelector(":scope > ul.sidebar-list");//这里的scope表示调用该方法的元素本身，也就是item
          const isOpen = item.hasAttribute("open");
          
          // 1. 先切换 open 属性（保持原有逻辑，用于 CSS 图标旋转等）
          if (isOpen) {
            item.removeAttribute("open");
          } else {
            item.setAttribute("open", "");
          }
          
          // 2. 执行高度动画
          toggleSubmenuHeight(submenu, !isOpen);
                 
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
  //为什么要给child也加？是因为3级分类吗？应该是把，暂时性的注释
  // const childItems = sidebar.querySelectorAll("ul.sidebar-list > li.category-child");
  // addToggleListeners(childItems);
});