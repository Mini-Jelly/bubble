/**
  * 全局平滑滚动
  */
import Lenis from "lenis";
// Lenis 的配套样式：给 [data-lenis-prevent] 容器加 overscroll-behavior: contain，
// 顺带处理 html 高度与 iframe 的指针事件
import "lenis/dist/lenis.css";

const lenis = new Lenis({
  // 由 Lenis 自持 rAF 循环，省掉手写 requestAnimationFrame 样板
  autoRaf: true,
  // 阻尼系数：每帧向目标位置靠拢的比例，直接决定手感。Lenis 默认 0.1 在 Windows 滚轮
  // （一格固定 100px、不像触控板有连续增量）上偏肉，0.22 是折中值：调大更跟手，调小更飘。
  lerp: 0.22,
  // 其余保持默认值：syncTouch: false 让移动端保留原生惯性（手感最好），
  // respectReducedMotion: true 使系统开启「减少动态效果」时自动退化为即时滚动
});

/**
  * 接管站内锚点点击
  *
  * 不能用 Lenis 的 anchors 选项：它不调用 preventDefault，浏览器原生的锚点瞬间跳转
  * 会抢在平滑之前把页面拽到位。自己接管后才能顺便补上 URL 的 hash
  * （preventDefault 会吞掉原生的地址栏更新）。
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
