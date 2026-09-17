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

  /**
   * 读一个时长令牌的毫秒数。
   *
   * 不要在任何地方写死毫秒 —— 调试时把 --dur-* 调长是常规操作，
   * 写死的值会立刻和实际动画对不上（离场的那一层在动画走完前就被删掉，
   * 看起来就是「二级菜单的内容凭空消失」）。
   */
  const cssMs = (name, fallback) => {
    const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const n = parseFloat(raw);
    if (!Number.isFinite(n)) return fallback;
    return raw.endsWith("ms") ? n : n * 1000;
  };

  /* 等 track 的横移真的结束。
     transitionend 优先（时长被改成多少都准），再用当前时长兜底 ——
     过渡被中断时 transitionend 不会触发，没有兜底就会永远卡住。 */
  const afterSlide = (fn) => {
    let done = false;
    const finish = () => {
      if (done) return;
      done = true;
      track.removeEventListener("transitionend", onEnd);
      window.clearTimeout(fallback);
      fn();
    };
    const onEnd = (event) => {
      if (event.target === track && event.propertyName === "transform") finish();
    };
    track.addEventListener("transitionend", onEnd);
    const fallback = window.setTimeout(finish, cssMs("--dur-sheet", 300) + 150);
  };

  let depth = 0;

  const levels = () => Array.from(track.children);

  const syncTrack = () => {
    track.style.transform = `translateX(-${depth * 100}%)`;
  };

  const syncHeader = () => {
    titleEl.textContent = levels()[depth]?.dataset.title || ROOT_TITLE;
    backBtn.hidden = depth === 0;
  };

  /**
   * 一层的内容高度。
   *
   * 不能用 scrollHeight —— 它返回 max(内容高, 当前高度)，
   * 当新的一层比当前矮时量出来还是当前高度，抽屉就永远变不矮。
   * 逐个子元素累加高度与上下外边距，才拿得到「自然高度」。
   */
  const levelContentHeight = (level) => {
    let height = 0;
    for (const child of level.children) {
      const style = getComputedStyle(child);
      height +=
        child.getBoundingClientRect().height +
        parseFloat(style.marginTop) +
        parseFloat(style.marginBottom);
    }
    return height;
  };

  /* 抽屉里「不随层级变化」的那部分高度：拖拽把手 + 标题栏 + 上下内边距。
     写 --sheet-height 时必须把它算进去 —— 只写层级内容高度的话，
     整个抽屉会被压成内容那么高，把手和标题栏吃掉的空间会从列表里扣，
     根层列表就被挤出一条滚动条。 */
  const chromeHeight = () => {
    const cs = getComputedStyle(sidebar);
    const outer = (el) => {
      if (!el) return 0;
      const s = getComputedStyle(el);
      return el.getBoundingClientRect().height + parseFloat(s.marginTop) + parseFloat(s.marginBottom);
    };
    return (
      parseFloat(cs.paddingTop) +
      parseFloat(cs.paddingBottom) +
      outer(sidebar.querySelector(".sidebar-handle")) +
      outer(sidebar.querySelector(".sidebar-header"))
    );
  };

  /* 抽屉高度 = 当前层级栈里最高的一层 —— 也就是「变长不变短」。
     钻进一个很短的二级分类时抽屉不变矮（否则刚滑过去就整个缩一下，很跳），
     二级更高时才长高；返回时栈变短，高度自然收回去。
     写入 --sheet-height 后由 CSS 的 height 过渡接管，是连续的而不是跳变。 */
  const syncHeight = (animate = true) => {
    const stack = levels().slice(0, depth + 1);
    if (stack.length === 0) return;

    let content = 0;
    for (const level of stack) content = Math.max(content, levelContentHeight(level));

    const height = Math.round(chromeHeight() + content);
    // 宽屏时 .sidebar 是 display:none，量出来全是 0；跳过，等打开时再算
    if (height <= 0) return;

    if (!animate) sidebar.style.transition = "none";
    sidebar.style.setProperty("--sheet-height", `${height}px`);
    if (!animate) {
      // 强制一次样式重算，再把过渡还回去，否则复位会被看到
      void sidebar.offsetHeight;
      sidebar.style.transition = "";
    }
  };

  /**
   * 把焦点送进某一层，但**不要让它滚动容器**。
   *
   * focus() 默认会把目标滚进视野。新的一层此刻还在右边屏幕外，而它所在的
   * .sidebar-body 是 overflow: hidden —— 这个容器**仍然可以被程序化滚动**，
   * 于是浏览器把容器横向滚了整整一屏。
   * 那个滚动和 track 的横移恰好方向相反、幅度相同，两者互相抵消，
   * 净位移永远是 0 —— 表现就是「数值上在动、画面上直接跳变」，并且会左右抖。
   */
  const focusIn = (level) => {
    const first = level.querySelector("a[href], button:not([disabled])");
    if (first) first.focus({ preventScroll: true });
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
    syncHeight();

    // 切层之后把焦点送进新的一层，键盘用户不会还停在上一层
    focusIn(level);
  };

  const pop = () => {
    if (depth === 0) return;
    const leaving = levels()[depth];
    depth -= 1;
    syncTrack();
    syncHeader();
    syncHeight();

    /* 焦点此刻还在离场那一层里，那一层被删掉后焦点会掉到 <body>，
       键盘用户就「丢」了位置。先把它接回上一层。 */
    focusIn(levels()[depth]);

    // 等横移真的走完再删，否则动画还在放，那一层的内容就先没了
    afterSlide(() => leaving.remove());
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
    syncHeight(false);
    // 强制一次样式重算，再把过渡还回去
    void track.offsetHeight;
    track.style.transition = "";
  };

  const drawer = createDrawer({
    root: sidebar,
    triggers: [trigger],
    scrim: getById("drawerScrim"),
    handle: sidebar.querySelector(".sidebar-handle"),
    /* 每次打开前重算一次高度。宽屏时抽屉是 display:none、量不出高度，
       而宽度跨回窄屏时不会重新初始化 —— 不重算就会带着旧值打开。
       必须放在 onBeforeOpen：syncHeight(false) 会临时写 inline transition: none，
       放在 onOpen 会把刚起步的滑入过渡就地掐掉，抽屉就变成「蹦出来」。 */
    onBeforeOpen: () => syncHeight(false),
    onClose: () => {
      window.setTimeout(() => {
        // 期间又被打开的话不要复位，否则会看到当前层突然消失
        if (!drawer.isOpen()) resetToRoot();
      }, cssMs("--dur-exit", 140) + 60);
    },
  });

  backBtn.addEventListener("click", pop);

  levels()[0].dataset.title = ROOT_TITLE;
  decorate(levels()[0]);
  syncHeader();
  // 初始高度不带过渡：否则首屏会看到抽屉从 0 长出来
  syncHeight(false);
});
