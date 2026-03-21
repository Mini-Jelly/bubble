import { getById } from "./global.js";
document.addEventListener("DOMContentLoaded", () => {
  const nav = getById("nav");
  const categoryList = nav.querySelectorAll("ul.category-list > li.category-child");
  const searchSubmit = nav.querySelector(".nav-form-submit");
  const inputField = getById("nav-form-input");
  const clearBtn = getById("nav-form-clean");

  // 添加图标
  const addIcon = (category_list) => {
    category_list.forEach((item) => {
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
  let isClearBtnVisible = false;
  inputField.addEventListener("input", () => {
    const hasInput = inputField.value.length > 0;
    clearBtn.style.display = hasInput ? "inline-block" : "none";
    isClearBtnVisible = hasInput;
  });

  clearBtn.addEventListener("click", () => {
    inputField.value = "";
    clearBtn.style.display = "none";
    isClearBtnVisible = false;
  });

  inputField.addEventListener("focus", () => searchSubmit.classList.add("rotate"));
  inputField.addEventListener("blur", () => searchSubmit.classList.remove("rotate"));
});
