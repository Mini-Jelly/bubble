import { getById } from "./global.js";
import { createDrawer } from "./drawer.js";

/* 手机端底部抽屉：钻取式导航。
 *
 * 为什么不是手风琴：基础列表 8 行，加上一个 7 项的展开组约 888px，
 * 而抽屉上限 80vh 在 390×844 上只有 675px —— 展开必然溢出、只能滚。
 * 改成钻取之后每层都短，抽屉不用滚。
 *
 * 层级结构：track 里横排若干等宽的 .sidebar-level，靠 translateX 切换。
 * 子级不在本级展开，而是把 <li> 里那个隐藏的 <ul> 复制成新的一层 ——
 * 子级本来就在 DOM 里（Typecho 的 listCategories 输出的），不必让 PHP 再输出一遍。
 * 这一并干掉了原先 toggleSubmenuHeight 那套 px→auto 的高度交还 hack。
 */
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = getById("sidebar");
  const trigger = getById("sidebarOutBtn");
  if (!sidebar || !trigger) return;

  const track = sidebar.querySelector('[data-role="track"]');
  const titleEl = sidebar.querySelector('[data-role="title"]');
  const backBtn = sidebar.querySelector(".sidebar-back");
  if (!track || !titleEl || !backBtn) return;

  const ROOT_TITLE = titleEl.textContent.trim() || "导航";
  /* 与 --dur-sheet（300ms）对齐：等横移动画走完再删掉离场的那一层，
     否则会看到它凭空消失 */
  const LEVEL_EXIT_MS = 320;

  let depth = 0;

  const levels = () => Array.from(track.children);

  const syncTrack = () => {
    track.style.transform = `translateX(-${depth * 100}%)`;
  };

  const syncHeader = () => {
    titleEl.textContent = levels()[depth]?.dataset.title || ROOT_TITLE;
    backBtn.hidden = depth === 0;
  };

  const push = (sub, name) => {
    const level = document.createElement("div");
    level.className = "sidebar-level";
    level.setAttribute("data-role", "level");
    level.dataset.title = name || ROOT_TITLE;
    level.appendChild(sub.cloneNode(true));

    track.appendChild(level);
    decorate(level);
    depth = levels().length - 1;
    syncTrack();
    syncHeader();

    // 切层之后把焦点送进新的一层，键盘用户不会还停在上一层
    const first = level.querySelector("a[href], button:not([disabled])");
    if (first) first.focus();
  };

  const pop = () => {
    if (depth === 0) return;
    const leaving = levels()[depth];
    depth -= 1;
    syncTrack();
    syncHeader();
    window.setTimeout(() => leaving.remove(), LEVEL_EXIT_MS);
  };

  /**
   * 给「有下级」的 li 补一个钻取箭头。
   *
   * 做成独立按钮而不是把整行当点击区：点名字是跳转、点箭头是下钻，是两个意图。
   * 合在一起必然出现「想展开却跳走了」。
   */
  const decorate = (root) => {
    root.querySelectorAll("li").forEach((li) => {
      const sub = li.querySelector(":scope > ul.sidebar-list");
      if (!sub) return;
      // 幂等：同一层被 decorate 两次不会挂上两个箭头
      if (li.querySelector(":scope > .sidebar-more")) return;

      const name = (li.querySelector(":scope > a")?.textContent || "").trim();
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "sidebar-more";
      btn.setAttribute("aria-label", name ? `展开「${name}」` : "展开下级");
      btn.innerHTML =
        '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
        '<path d="M9.3 5.3a1 1 0 0 1 1.4 0l6 6a1 1 0 0 1 0 1.4l-6 6a1 1 0 1 1-1.4-1.4L14.6 12 9.3 6.7a1 1 0 0 1 0-1.4z"/>' +
        "</svg>";

      btn.addEventListener("click", (event) => {
        event.stopPropagation();
        push(sub, name);
      });

      li.appendChild(btn);
    });
  };

  /* 回到根层级。只在抽屉关掉之后调用，而且要关掉过渡 ——
     复位是「下次打开时的初始状态」，不该被看到。 */
  const resetToRoot = () => {
    depth = 0;
    track.style.transition = "none";
    syncTrack();
    levels()
      .slice(1)
      .forEach((level) => level.remove());
    syncHeader();
    // 强制一次样式重算，再把过渡还回去
    void track.offsetHeight;
    track.style.transition = "";
  };

  const drawer = createDrawer({
    root: sidebar,
    triggers: [trigger],
    scrim: getById("drawerScrim"),
    handle: sidebar.querySelector(".sidebar-handle"),
    onClose: () => {
      window.setTimeout(() => {
        // 期间又被打开的话不要复位，否则会看到当前层突然消失
        if (!drawer.isOpen()) resetToRoot();
      }, LEVEL_EXIT_MS);
    },
  });

  backBtn.addEventListener("click", pop);

  levels()[0].dataset.title = ROOT_TITLE;
  decorate(levels()[0]);
  syncHeader();
});
