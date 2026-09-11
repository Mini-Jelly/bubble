import { getById } from "./global.js";
import lenis from "./smooth_scroll.js";

document.addEventListener("DOMContentLoaded", () => {
  const backToTop = getById("backToTop");
  if (!backToTop) return;

  const update = () => {
    // 滚过一屏（首屏内容翻完）才出现，避免和首屏内容抢视线
    backToTop.classList.toggle("is-visible", window.scrollY > window.innerHeight);
  };

  // 滚动回调里只读 scrollY、切一次类名，但高频滚动下仍会密集触发，
  // 用 rAF 合帧，保证每帧最多算一次
  let ticking = false;
  window.addEventListener(
    "scroll",
    () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        update();
        ticking = false;
      });
    },
    { passive: true }
  );

  // 刷新后可能停留在页面中部，首帧就要给出正确状态，不能等第一次滚动
  update();

  backToTop.addEventListener("click", () => {
    lenis.scrollTo(0);
  });
});
