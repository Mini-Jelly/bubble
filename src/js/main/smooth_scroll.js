/**
 * 全局平滑滚动
 *
 * 目的：让鼠标滚轮 / 触控板的滚动带上缓动，而不是每格 100px 瞬时跳变。
 *
 * 为什么不能只用 CSS：
 *   scroll-behavior: smooth 只作用于「程序化滚动」（锚点跳转、scrollTo、
 *   scrollIntoView），对鼠标滚轮完全无效 —— 滚轮走的是浏览器原生滚动，
 *   CSS 无从介入。要平滑滚轮就必须由 JS 接管 wheel 事件。
 *
 * 为什么选 Lenis：
 *   它用原生 scrollTop 驱动，而非给内容套一层 transform。因此本站 3 处
 *   position: fixed（.sidebar / .sidebar-backdrop / .post-toc）以及文章目录
 *   依赖的 IntersectionObserver 都不受影响；transform 驱动的类库
 *   （smooth-scrollbar、GSAP ScrollSmoother）会把它们全部破坏。
 */
import Lenis from "lenis";
// Lenis 的配套样式：给 [data-lenis-prevent] 容器加 overscroll-behavior: contain，
// 顺带处理 html 高度与 iframe 的指针事件
import "lenis/dist/lenis.css";

const lenis = new Lenis({
  // 由 Lenis 自持 rAF 循环，省掉手写 requestAnimationFrame 样板
  autoRaf: true,
  // 阻尼系数：每帧向目标位置靠拢的比例。Lenis 默认 0.1，在 Windows 鼠标滚轮上
  // （一格固定 100px、不像触控板有连续增量）偏肉；0.22 是「看得出缓动、松手立刻
  // 停住」的折中值。这个数直接决定手感：调大更跟手，调小更飘。
  lerp: 0.22,
  // 其余保持默认值，逐条说明为什么不改：
  //   smoothWheel: true         滚轮平滑（本模块的存在意义）
  //   syncTouch: false          移动端不接管触摸，保留原生惯性 —— 原生的手感最好
  //   respectReducedMotion: true 系统开启「减少动态效果」时自动退化为即时滚动
});

/**
 * 接管站内锚点点击
 *
 * 不能依赖 Lenis 的 anchors 选项：它只在 click 回调里调 scrollTo，并不调用
 * preventDefault（其源码中的 preventDefault 只出现在 wheel / touch 处理里），
 * 于是浏览器原生的锚点「瞬间跳转」会抢在平滑之前把页面拽到位，平滑等于白做。
 *
 * 这里自己接管，顺便把 URL 的 hash 补上 —— preventDefault 会吞掉原生的地址栏更新。
 */
document.addEventListener("click", (event) => {
  const target = event.target;
  if (!(target instanceof Element)) return;

  const link = target.closest('a[href^="#"]');
  if (!link) return;

  const href = link.getAttribute("href");
  // 侧边栏里的 <a href="#">（占位链接）保持原样，不做接管
  if (!href || href === "#") return;

  // 用 getElementById 而不是 querySelector：id 里含 : . 等字符时选择器会直接抛错
  let anchor;
  try {
    anchor = document.getElementById(decodeURIComponent(href.slice(1)));
  } catch {
    return; // href 里有非法的百分号转义
  }
  if (!anchor) return;

  event.preventDefault();

  // 补回原生行为：把锚点写进地址栏，链接才能被复制分享
  history.pushState(null, "", href);
  // 目标上方留 20px，标题不贴着视口顶
  lenis.scrollTo(anchor, { offset: -20 });
});

export default lenis;
