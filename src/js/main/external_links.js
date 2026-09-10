/**
 * 主题设置「站外链接新窗口打开」的落地实现。
 *
 * 不能直接用 <base target="_blank">：它会让站内分页、目录锚点（#xxx）
 * 以及 <a href="#"> 这类占位链接也统统新开标签页。
 * 这里改成只给「站外链接」补 target，站内跳转保持当前窗口。
 */
document.addEventListener("DOMContentLoaded", () => {
  if (document.documentElement.getAttribute("open-new-window") !== "on") return;

  const isExternal = (url) => {
    // 只有带协议（含协议相对）的地址才可能是站外链接
    if (!/^(https?:)?\/\//i.test(url)) return false;
    try {
      return new URL(url, location.href).host !== location.host;
    } catch {
      return false;
    }
  };

  document.querySelectorAll("a[href]").forEach((link) => {
    if (link.target === "_blank") return; // 已经显式指定过，不重复处理

    const href = link.getAttribute("href");
    if (!href || href.startsWith("#")) return; // 页内锚点不处理
    if (!isExternal(href)) return;

    link.setAttribute("target", "_blank");
    // 不加 noopener 时，新标签页可以拿到 window.opener 反向操作原页面
    link.setAttribute("rel", "noopener noreferrer");
  });
});
