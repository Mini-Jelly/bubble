/* 抽屉控制器 —— 导航抽屉与设置抽屉共用这一套。
 *
 * 为什么抽出来：两个抽屉要各自实现开合、遮罩、Esc、焦点环、焦点归还、拖拽关闭、
 * 断点切换时自动关闭 —— 写两遍必然写歪，而且「visibility 参与过渡会让 focus() 静默失败」
 * 这类坑要踩两次。
 *
 * 状态只有一个：根元素上的 .is-open。触发按钮的 aria-expanded、遮罩、
 * 全局的 has-drawer-open 都由它派生，不存第二份真源。
 */

import lenis from "./smooth_scroll.js";

/* 抽屉只在窄屏存在：≥834px 时导航恢复完整形态、设置面板变回锚定浮层。
   越过断点还开着的抽屉必须自己关掉，否则横屏后会看到抽屉停在页面中间。 */
const NARROW = "(max-width: 833px)";
const narrowQuery = window.matchMedia(NARROW);

const FOCUSABLE =
  "a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex='-1'])";

const drawers = [];
let scrimEl = null;
let scrimBound = false;

/** 只取真正可见的可聚焦元素：抽屉里可能有 display:none 的层级，不能被 Tab 选中 */
function visibleFocusables(root) {
  return Array.from(root.querySelectorAll(FOCUSABLE)).filter(
    (el) => el.offsetWidth > 0 || el.offsetHeight > 0
  );
}

/* 背景滚动锁。
 *
 * 蒙版只是视觉遮挡，不拦滚动 —— 不锁的话用户能隔着蒙版把正文滑走。三件事都要做：
 *   1) lenis.stop() —— Lenis 用原生 scrollTop 驱动，光靠 CSS 的 overflow: hidden
 *      拦不住它（它是程序化 scrollTo，不受 overflow 限制）
 *   2) html 上的 .has-drawer-open 给 overflow: hidden —— 拦住原生滚动
 *      （滚动条拖动、键盘翻页，以及 syncTouch: false 时的触摸惯性）
 *   3) 补偿滚动条宽度 —— 锁定瞬间滚动条消失会让整页横向位移（窄屏桌面窗口上约 15px），
 *      给 body 补等宽 padding 就看不出来。必须在加 class 之前量，那时滚动条还在。
 */
let scrollLocked = false;

function lockScroll(locked) {
  if (locked === scrollLocked) return;
  scrollLocked = locked;

  const root = document.documentElement;
  if (locked) {
    root.style.setProperty("--scrollbar-gap", `${window.innerWidth - root.clientWidth}px`);
    root.classList.add("has-drawer-open");
    if (lenis) lenis.stop();
  } else {
    root.classList.remove("has-drawer-open");
    root.style.removeProperty("--scrollbar-gap");
    if (lenis) lenis.start();
  }
}

/**
 * @param {object}   config
 * @param {Element}  config.root       抽屉根元素，打开时加上 .is-open
 * @param {Element[]} config.triggers  触发按钮（可以有多个，比如手机端与桌面端各一个）
 * @param {Element}  [config.scrim]    共享遮罩
 * @param {Element}  [config.handle]   拖拽把手
 * @param {boolean|Function} [config.modal=true] 是否走模态行为（遮罩 + 焦点环）。
 *        传函数则每次实时求值 —— 设置面板在窄屏是模态抽屉、宽屏是普通浮层。
 * @param {Function} [config.onOpen]
 * @param {Function} [config.onClose]
 */
export function createDrawer(config) {
  const { root, triggers = [], scrim, handle, onOpen, onClose } = config;
  if (!root) return null;
  if (scrim) scrimEl = scrim;

  let restoreTo = null;

  const isOpen = () => root.classList.contains("is-open");
  const isModal = () => {
    const m = config.modal;
    return typeof m === "function" ? !!m() : m !== false;
  };

  /* 遮罩与滚动锁是共享的：只要还有「模态的」抽屉开着就生效。
     非模态形态（宽屏的设置浮层）不该把整页压暗，也不该锁滚动。 */
  function syncScrim() {
    const anyModalOpen = drawers.some((d) => d.isOpen() && d.isModal());
    if (scrimEl) scrimEl.classList.toggle("is-visible", anyModalOpen);
    lockScroll(anyModalOpen);
  }

  function open(from) {
    if (isOpen()) return;

    // 互斥：同一时刻只允许一个抽屉开着，否则遮罩与焦点会打架
    drawers.forEach((d) => {
      if (d !== api && d.isOpen()) d.close();
    });

    root.classList.add("is-open");
    triggers.forEach((t) => t.setAttribute("aria-expanded", "true"));
    /* aria-modal 必须跟着形态走，不能写死在标记里 ——
       设置面板窄屏是模态抽屉、宽屏是普通浮层，写死会让屏幕阅读器在宽屏下
       也把背景内容整片屏蔽掉。 */
    if (isModal()) root.setAttribute("aria-modal", "true");
    else root.removeAttribute("aria-modal");
    restoreTo = from || triggers[0] || null;
    syncScrim();

    /* 必须读一次 offsetHeight 强制样式重算：抽屉靠 visibility 隐藏，
       浏览器没算出新状态之前 focus() 会因为「元素不可聚焦」而静默失败 */
    void root.offsetHeight;
    const first = visibleFocusables(root)[0];
    if (first) first.focus();

    if (onOpen) onOpen();
  }

  function close() {
    if (!isOpen()) return;
    root.classList.remove("is-open");
    root.removeAttribute("aria-modal");
    triggers.forEach((t) => t.setAttribute("aria-expanded", "false"));
    if (onClose) onClose();
    syncScrim();
    // 焦点还给触发按钮，键盘用户不会「丢失」焦点
    if (restoreTo && document.contains(restoreTo)) restoreTo.focus();
    restoreTo = null;
  }

  function toggle(from) {
    if (isOpen()) close();
    else open(from);
  }

  /** 焦点环：在首尾两个可聚焦元素之间回绕，Tab 不会漏到背景内容里 */
  function trapFocus(event) {
    const items = visibleFocusables(root);
    if (items.length === 0) return;
    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || !root.contains(active))) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  }

  /* ---------- 拖拽把手 ----------
     把手如果不响应拖拽就只是装饰，用户会去推它、推不动就觉得整个抽屉是坏的。
     只允许往下拖：往上拖没有对应动作，放手还会弹回来，白给一段无效行程。 */
  if (handle) {
    let startY = 0;
    let delta = 0;
    let dragging = false;

    handle.addEventListener("pointerdown", (event) => {
      if (event.button !== 0) return;
      dragging = true;
      startY = event.clientY;
      delta = 0;
      // 拖拽期间必须关掉过渡，否则面板会「追着」指针走，手感像在拖一块橡皮
      root.style.transition = "none";
      handle.setPointerCapture(event.pointerId);
    });

    handle.addEventListener("pointermove", (event) => {
      if (!dragging) return;
      delta = Math.max(0, event.clientY - startY);
      root.style.transform = `translateY(${delta}px)`;
    });

    const endDrag = () => {
      if (!dragging) return;
      dragging = false;
      // 拖过面板高度的三分之一就判定为关闭意图，否则弹回去
      const shouldClose = delta > root.offsetHeight / 3;
      // 先恢复 CSS 过渡再清掉行内 transform：浏览器会从当前渲染位置补间到目标值，
      // 于是「拖到一半松手」也是平滑的，不会跳一下
      root.style.transition = "";
      root.style.transform = "";
      if (shouldClose) close();
    };

    handle.addEventListener("pointerup", endDrag);
    handle.addEventListener("pointercancel", endDrag);
  }

  const api = { root, isOpen, isModal, open, close, toggle, trapFocus, triggers };
  drawers.push(api);

  triggers.forEach((trigger) => {
    trigger.addEventListener("click", (event) => {
      // 不冒泡：否则 document 上的「点外部关闭」会立刻又把它关掉
      event.stopPropagation();
      toggle(trigger);
    });
  });

  /* 点蒙版关闭。蒙版是共享的，所以整个模块只挂一次 ——
     关掉当前开着的那个（同一时刻最多一个）。 */
  if (scrim && !scrimBound) {
    scrimBound = true;
    scrim.addEventListener("click", () => {
      drawers.forEach((d) => {
        if (d.isOpen()) d.close();
      });
    });
  }

  return api;
}

/* Esc 与焦点环只注册一次，不为每个抽屉各写一遍。
   有多个抽屉同时存在时，只作用于「当前开着的那个」。 */
document.addEventListener("keydown", (event) => {
  const current = drawers.find((d) => d.isOpen());
  if (!current) return;

  if (event.key === "Escape") {
    event.preventDefault();
    current.close();
    return;
  }

  // 非模态形态（宽屏设置浮层）不锁焦点，Tab 应该能自然离开
  if (event.key === "Tab" && current.isModal()) {
    current.trapFocus(event);
  }
});

// 宽度越过断点时把所有抽屉收掉
narrowQuery.addEventListener("change", (event) => {
  if (event.matches) return;
  drawers.forEach((d) => {
    if (d.isOpen()) d.close();
  });
});

/* 从往返缓存恢复时页面没有重新渲染，上一次留下的 .is-open 会原样冻在页面上，
   必须手动清掉，否则会看到一个永远关不掉的抽屉。 */
window.addEventListener("pageshow", (event) => {
  if (!event.persisted) return;
  drawers.forEach((d) => {
    if (d.isOpen()) d.close();
  });
});

export { narrowQuery };
