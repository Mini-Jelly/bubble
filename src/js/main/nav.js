import { getById } from "./global.js";
document.addEventListener("DOMContentLoaded", () => {
  const nav = getById("nav");
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
