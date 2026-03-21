document.addEventListener("DOMContentLoaded", function () {
  const container = document.querySelector(".container");
  const options = document.querySelectorAll(".bubble-option");
  let activeItem = document.querySelector(".config-item.active");

  // 初始化选项显示
  if (activeItem) {
    showOption(activeItem.getAttribute("data-target"));
  }

  // 点击事件委托
  container.addEventListener("click", function (event) {
    const target = event.target.closest(".config-item");
    //防止重复点击
    if (target && !target.classList.contains("active")) {
      //如果存在激活选项，先清除激活的选项
      if (activeItem) {
        activeItem.classList.remove("active");
      }
      //给当前点击的选项添加类
      target.classList.add("active");
      //更新当前激活的选项
      activeItem = target;
      //调用函数显示激活的选项
      showOption(target.getAttribute("data-target"));
    }
  });

  // 显示对应选项
  function showOption(target) {
    options.forEach(function (option) {
      option.style.display = option.classList.contains(target) ? "block" : "none";
    });
  }
});
