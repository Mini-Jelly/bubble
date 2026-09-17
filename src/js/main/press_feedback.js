/* 按压反馈的行程保证。
 *
 * 为什么需要它：:active 的寿命由浏览器按真实指针状态决定，快速点击只有几十毫秒，
 * CSS 没有任何手段能延长（transition-delay 只能延后开始，补不完被打断的过渡）。
 * 结果是轻点一下只走完一小截行程，看起来像没反应。
 *
 * 做法：pointerdown 时给被按下的控件加 .is-pressed，并保证它在
 * --dur-press + --press-dwell 之内不被移除 —— 指针提前抬起不是立刻移除，
 * 而是等最短时长走完再移除，于是极快的点击也能被眼睛捕捉到。
 *
 * 导航型链接额外加 .is-pending：从松手到页面卸载（本机实测仅 59ms）物理上播不完
 * 「弹起」，所以保持按压态直到卸载，而不是让它弹回去。:active 只作无 JS 时的兜底。
 */

/* 从 CSS 读时长而不是写死常量：--dur-press / --press-dwell 会在
   prefers-reduced-motion 下归零，读令牌就不必在 JS 里再判一次媒体查询。 */
function tokenMs(name) {
  const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  const value = parseFloat(raw);
  if (Number.isNaN(value)) return 0;
  return raw.endsWith("ms") ? value : value * 1000;
}

let minHold = 0;
function refreshMinHold() {
  minHold = tokenMs("--dur-press") + tokenMs("--press-dwell");
}
refreshMinHold();

const motionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
if (motionQuery.addEventListener) {
  motionQuery.addEventListener("change", refreshMinHold);
}

/**
 * 找出这次按下应该反馈在哪个元素上。
 *
 * 卡片是特例：只有在「按在卡片内的链接上」时才把整张卡片当作按压目标。
 * 按在摘要文字上不触发 —— 否则拖选文字也会让整张卡片缩一下。
 *
 * @param {Element} start  pointerdown 的真实落点
 * @returns {Element|null}
 */
function resolvePressTarget(start) {
  const link = start.closest("a[href], button, [role='button']");
  const card = start.closest(".article-card");

  if (card) return link ? card : null;
  return link;
}

/**
 * 判断这个链接是否会导致当前文档卸载。
 * 不卸载的（新标签、下载、锚点、站外、当前页）不该走 .is-pending ——
 * 它们不会卸载，保持按压态到「卸载」等于永远卡住。
 */
function navigatesAway(link) {
  if (!link || !link.matches("a[href]")) return false;
  if (link.target && link.target !== "_self") return false;
  if (link.hasAttribute("download")) return false;

  const href = link.getAttribute("href");
  if (!href || href.startsWith("#")) return false;
  if (/^(mailto:|tel:|javascript:)/i.test(href)) return false;

  try {
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin) return false;
    return url.pathname + url.search !== location.pathname + location.search;
  } catch {
    return false;
  }
}

let active = null;
let releaseTimer = 0;

function clearReleaseTimer() {
  if (releaseTimer) {
    clearTimeout(releaseTimer);
    releaseTimer = 0;
  }
}

function resetActive() {
  if (!active) return;
  active.el.classList.remove("is-pressed");
  active.el.classList.remove("is-pending");
  active = null;
  clearReleaseTimer();
}

document.addEventListener(
  "pointerdown",
  (event) => {
    // 只处理主键：右键 / 中键不产生按压反馈
    if (event.button !== 0 || event.defaultPrevented) return;
    if (!(event.target instanceof Element)) return;

    // 上一次按压还没收尾就换目标（连点两处），先把旧目标复位
    resetActive();

    const el = resolvePressTarget(event.target);
    if (!el) return;

    const directLink = event.target.closest("a[href]");
    active = {
      el,
      startedAt: performance.now(),
      navigates: navigatesAway(directLink),
    };
    el.classList.add("is-pressed");
  },
  { passive: true }
);

/**
 * 松手。
 * @param {boolean} willNavigate 这次松手是否会触发导航
 */
function release(willNavigate) {
  if (!active) return;
  const { el, startedAt } = active;
  active = null;

  if (willNavigate) {
    // 保持按压态直到卸载。正常路径下不设时长 —— 页面卸载时整个文档一起消失
    el.classList.add("is-pending");

    /* 兜底：导航可能根本没发生 —— 链接被别的脚本 preventDefault、被扩展拦下、
       或者用户中途取消了加载。这些情况下页面不会卸载，按压态就会永远挂在元素上，
       看起来像卡住了。给一个上限，超时自己复位。 */
    clearReleaseTimer();
    releaseTimer = setTimeout(() => {
      releaseTimer = 0;
      el.classList.remove("is-pressed");
      el.classList.remove("is-pending");
    }, 1200);
    return;
  }

  const elapsed = performance.now() - startedAt;
  clearReleaseTimer();
  releaseTimer = setTimeout(
    () => {
      releaseTimer = 0;
      el.classList.remove("is-pressed");
    },
    Math.max(0, minHold - elapsed)
  );
}

document.addEventListener(
  "pointerup",
  (event) => {
    if (!active) return;
    // 按住修饰键是「在新标签打开」的意图，页面不卸载，走正常的弹起
    const plain = !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
    release(active.navigates && plain);
  },
  { passive: true }
);

// 指针被系统抢走（来电、手势返回）时不会触发 pointerup，必须单独收尾
document.addEventListener("pointercancel", () => release(false), { passive: true });

/* 从往返缓存恢复时，上一次留下的按压态会原样冻在页面上（页面根本没重新渲染），
   必须手动清掉，否则会看到某个按钮一直是按下的样子。 */
window.addEventListener("pageshow", (event) => {
  if (!event.persisted) return;
  document.querySelectorAll(".is-pressed, .is-pending").forEach((el) => {
    el.classList.remove("is-pressed");
    el.classList.remove("is-pending");
  });
  active = null;
  clearReleaseTimer();
});
