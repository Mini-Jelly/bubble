/**
 * 链接打开方式：读 <html> 上的 link-target-internal / link-target-external
 * （由 views/header.php 写入），逐条给页面里的链接补 target。
 *
 * 不覆盖作者显式写过的 target；跳过 .spotlight / [download] / 页内锚点
 * 与 mailto: / tel: / javascript:。
 */
document.addEventListener("DOMContentLoaded", () => {
  const html = document.documentElement;
  const policy = {
    internal: html.getAttribute("link-target-internal") || "_self",
    external: html.getAttribute("link-target-external") || "_self",
  };

  // 两个方向都是当前窗口时无事可做：不写 target 本来就是当前窗口
  if (policy.internal !== "_blank" && policy.external !== "_blank") return;

  /* 只有带协议（含协议相对）的地址可能是站外链接，其余一律按站内处理 */
  const isExternal = (url) => {
    if (!/^(https?:)?\/\//i.test(url)) return false;
    try {
      return new URL(url, location.href).host !== location.host;
    } catch {
      return false;
    }
  };

  document.querySelectorAll("a[href]").forEach((link) => {
    // 作者显式指定过 target（_blank / _self / 具名窗口），不覆盖
    if (link.hasAttribute("target")) return;
    // 灯箱：点击由 spotlight.js 接管，补 target 会把它变成新标签页
    if (link.classList.contains("spotlight")) return;
    if (link.hasAttribute("download")) return;

    const href = link.getAttribute("href");
    if (!href || href.startsWith("#")) return; // 页内锚点不处理
    if (/^(mailto:|tel:|javascript:)/i.test(href)) return;

    if ((isExternal(href) ? policy.external : policy.internal) !== "_blank") return;

    link.setAttribute("target", "_blank");

    /* 新标签页拿不到 window.opener；rel 是合并而非覆盖，正文里的 rel="nofollow" 不能被抹掉 */
    const tokens = (link.getAttribute("rel") || "").split(/\s+/).filter(Boolean);
    ["noopener", "noreferrer"].forEach((token) => {
      if (!tokens.includes(token)) tokens.push(token);
    });
    link.setAttribute("rel", tokens.join(" "));
  });
});
