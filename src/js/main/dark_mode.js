import { getById, setHtml } from "./global.js";
//读取=>设置=>监听
document.addEventListener("DOMContentLoaded", () => {
  const dropdownBtn = getById("dropdown-btn");
  const autoBtn = getById("dropdown-btn-auto");
  const lightBtn = getById("dropdown-btn-light");
  const darkBtn = getById("dropdown-btn-dark");

  //设置主题模式
  const setTheme = (themeMode) => {
    //获取形参
    let theme = themeMode;
    //过滤auto
    if (theme === "auto") {
      const prefersDarkMode = window.matchMedia("(prefers-color-scheme: dark)").matches;
      theme = prefersDarkMode ? "dark" : "light";
    }
    // 设置新的主题模式
    setHtml("color-scheme", theme);
    // 保存设置到本地(auto||light||dark)
    localStorage.setItem("theme", themeMode);
    // 更新按钮图标(auto||light||dark)
    updateButtonIcon(themeMode);
    // 设置按钮的active状态
    setActiveButton(themeMode);
  };

  //更新按钮图标
  const updateButtonIcon = (theme) => {
    let iconUse;
    let ariaLabel;

    // 根据选择的主题更新图标
    switch (theme) {
      case "auto":
        iconUse = "#icon-auto";
        ariaLabel = "Auto mode";
        break;
      case "light":
        iconUse = "#icon-light";
        ariaLabel = "Light mode";
        break;
      case "dark":
        iconUse = "#icon-dark";
        ariaLabel = "Dark mode";
        break;
      default:
        iconUse = "#icon-auto";
        ariaLabel = "Auto mode";
    }

    // 更新按钮图标
    const svg = dropdownBtn.querySelector("svg use");
    svg.setAttribute("href", iconUse);
    dropdownBtn.setAttribute("aria-label", ariaLabel);
  };

  // 设置按钮的active状态
  const setActiveButton = (selectedMode) => {
    const buttons = [autoBtn, lightBtn, darkBtn];

    buttons.forEach((button) => {
      if (button.id === `dropdown-btn-${selectedMode}`) {
        button.setAttribute("active", "");
      } else {
        button.removeAttribute("active");
      }
    });
  };

  //读取并应用之前保存的主题
  const storedTheme = localStorage.getItem("theme");
  if (storedTheme == null || storedTheme == "auto") {
    //用户首次访问，根据用户喜好设置主题模式 或者 用户设置的是auto
    setTheme("auto");
  } else if (storedTheme === "light" || storedTheme === "dark") {
    setTheme(storedTheme);
  } else {
    console.log("非法的stored:" + storedTheme);
    return;
  }

  //监听选择框的变化
  // 设置按钮点击事件监听器
  autoBtn.addEventListener("click", () => {
    setTheme("auto");
  });

  lightBtn.addEventListener("click", () => {
    setTheme("light");
  });

  darkBtn.addEventListener("click", () => {
    setTheme("dark");
  });

  // 监听系统主题变化（只在选择 'auto' 时有效）
  window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => {
    //无论用户什么时候访问，绝对存在localStorage
    //当系统的颜色方案发生变化，并且用户当前选择的是auto
    if (localStorage.getItem("theme") === "auto") {
      setTheme("auto");
    }
  });
});
