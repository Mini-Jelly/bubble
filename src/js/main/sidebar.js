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
      // 在显示 sidebar 时，绑定 backdrop 的点击事件
      backdrop.addEventListener("click", closeSidebar);
    } else {
      closeSidebar();
      // 移除 backdrop 的点击事件监听器
      backdrop.removeEventListener("click", closeSidebar);
    }
  });

  // 递归遍历所有符合条件的li元素，并为其绑定点击事件
  const addToggleListeners = (allParentItems) => {
    allParentItems.forEach((item) => {
      // 只有在 childElementCount > 1 的情况下，才意味着该菜单有子分类
      if (item.childElementCount > 1) {
        //拥有子分类的菜单添加图标类
        item.classList.add("toggle-icon");
        //对拥有子分类的元素添加点击监听事件
        item.addEventListener("click", (event) => {
          // 切换当前 <li> 的展开状态
          if (item.hasAttribute("open")) {
            // 移除 open 状态
            item.removeAttribute("open");
          } else {
            item.setAttribute("open", "");
          }
          // 阻止事件冒泡，避免触发父级菜单的点击事件
          event.stopPropagation();
        });

      } else if (item.childElementCount === 1 && item.childNodes.length === 1) {
        //扩大点击范围
        item.childNodes[0].style.display = "block";
      }
    });
  };

  // 获取所有具有 category-parent 类的 <li> 元素
  const parentItems = sidebar.querySelectorAll("ul.sidebar-list > li.category-parent");
  const childItems = sidebar.querySelectorAll("ul.sidebar-list > li.category-child");
  addToggleListeners(parentItems);
  addToggleListeners(childItems);
});
