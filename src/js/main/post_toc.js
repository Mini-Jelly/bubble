import { getById } from './global.js';

document.addEventListener('DOMContentLoaded', function () {
  // 0. 主题设置里关闭「文章目录」时，页面不会输出这个按钮，直接不做任何事
  const tocToggleBtn = getById('tocToggleBtn');
  if (!tocToggleBtn) return;

  // 1. 拿到对应的内容容器，对应post.php中的class="post-content"
  const postContent = document.querySelector('.post-content');
  if (!postContent) return; // 应对拿不到的情况

  // 2. 检测其中包含的 h1~h6
  const headers = postContent.querySelectorAll('h1, h2, h3, h4, h5, h6');

  // 3. 如果没有检测到任何标题，则不生成目录，即使生成了也是空的
  if (headers.length === 0) return;

  // 4. 计算最小层级
  let minLevel = 7; // 初始值设为最大可能+1
  headers.forEach((heading) => {
    const level = parseInt(heading.tagName[1]);
    if (level < minLevel) minLevel = level;
  });

  // 5. 创建容器并准备目录内容（容器应该包含目录标题和目录列表）
  const tocContainer = document.createElement('div');

  // 6. 为容器设置id并插入html
  tocContainer.id = 'post-toc';
  tocContainer.className = 'post-toc d-none'; // 目录默认隐藏
  // 滚动容器下移到了 <ul>：外壳是 flex 列布局（overflow: hidden），
  // 只有列表本身会滚动。data-lenis-prevent 必须挂在真正滚动的那个元素上，
  // 否则长目录的滚轮仍会被 Lenis 接管成滚动正文（滚动穿透）。
  tocContainer.innerHTML = `<div id="post-toc-header" class="post-toc-header">
    <h2>文章目录</h2>
    <button id="tocCloseBtn" class="post-toc-close-btn">❌</button>
    </div>
    <ul id="toc-list" data-lenis-prevent></ul>`;

  // 7. 选中容器内部的目录列表
  const tocList = tocContainer.querySelector('#toc-list');

  // 8. 性能优化：使用 DocumentFragment 减少重排
  const fragment = document.createDocumentFragment();

  // 9. 生成目录项 & 传递缩进值
  headers.forEach((heading, index) => {
    // 给源文章内容的 H1 ~ H6 标题
    // 仅当标题无 ID 时生成，避免覆盖原有锚点
    if (!heading.id) {
      heading.id = `toc-${index}`;
    }

    // 计算相对层级：核心算法
    const currentLevel = parseInt(heading.tagName[1]);
    const relativeLevel = currentLevel - minLevel;

    // 创建 DOM 元素
    const li = document.createElement('li');
    li.className = 'post-toc-item'; // 统一类名，无冗余

    // 核心：通过 CSS 变量传递缩进层级 (而非内联样式)
    li.style.setProperty('--toc-level', relativeLevel);

    const a = document.createElement('a');
    a.href = `#${heading.id}`;
    a.textContent = heading.textContent;

    li.appendChild(a);
    fragment.appendChild(li);
  });

  // 10. 批量插入到 DOM
  tocList.appendChild(fragment);

  // 11. 生成目录并插入对应位置（此处示例插入到内容之前，你也可以改为 document.body）
  postContent.parentNode.insertBefore(tocContainer, postContent);

  // 12. 目录可拖拽：只把标题栏当作把手，避免和右下角的缩放手势冲突
  enableDrag(tocContainer, getById('post-toc-header'));

  /**
   * 让 el 可以按 handle 拖拽移动，并把落点钳制在视口内。
   *
   * 为什么必须钳制：面板是 position: fixed，left/top 一旦写成像素值，就脱离了
   * CSS 里「left: 20px」那套兜底。拖出视口后标题栏点不到，面板等于把自己弄丢，
   * 只能刷新页面。钳制后无论怎么拖，面板始终完整可见。
   *
   * 为什么用 Pointer Events + setPointerCapture 而不是 document 上的 mousemove：
   * 指针捕获后事件会持续派发给把手，鼠标移出窗口也不会中断拖拽，天然支持触屏，
   * 也不必再手动往 document 上挂/摘监听。
   */
  function enableDrag(el, handle) {
    const dragHandle = handle || el;
    if (!dragHandle) return;

    let pointerId = null;
    // 按下时缓存一次尺寸：拖拽过程中尺寸不变，没必要每次移动都读一遍布局
    let size = { width: 0, height: 0 };
    let offsetX = 0;
    let offsetY = 0;
    // 只有真正拖动过才写内联 left/top；否则保持 CSS 的初始定位，
    // 窗口缩放时也就不会把一个「没人动过」的面板钉死在像素坐标上
    let hasMoved = false;

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const place = (left, top) => {
      const maxLeft = Math.max(0, window.innerWidth - size.width);
      const maxTop = Math.max(0, window.innerHeight - size.height);
      el.style.left = `${clamp(left, 0, maxLeft)}px`;
      el.style.top = `${clamp(top, 0, maxTop)}px`;
    };

    dragHandle.addEventListener('pointerdown', (event) => {
      // 只响应鼠标左键；标题栏里的关闭按钮保持原本的点击行为
      if (event.button !== 0 || event.target.closest('a, button')) return;

      const rect = el.getBoundingClientRect();
      size = { width: rect.width, height: rect.height };
      offsetX = event.clientX - rect.left;
      offsetY = event.clientY - rect.top;

      pointerId = event.pointerId;
      dragHandle.setPointerCapture(pointerId);
      event.preventDefault(); // 防止拖拽时选中文字
    });

    dragHandle.addEventListener('pointermove', (event) => {
      if (pointerId === null || event.pointerId !== pointerId) return;
      hasMoved = true;
      place(event.clientX - offsetX, event.clientY - offsetY);
    });

    const endDrag = (event) => {
      if (pointerId === null || event.pointerId !== pointerId) return;
      // 浏览器可能已自动释放捕获，release 前先确认，避免抛错打断收尾
      if (dragHandle.hasPointerCapture(pointerId)) {
        dragHandle.releasePointerCapture(pointerId);
      }
      pointerId = null;
    };

    dragHandle.addEventListener('pointerup', endDrag);
    dragHandle.addEventListener('pointercancel', endDrag);

    // 窗口变小后，原落点可能已经落在视口外，重新钳一次
    window.addEventListener('resize', () => {
      if (!hasMoved || pointerId !== null) return;
      const rect = el.getBoundingClientRect();
      size = { width: rect.width, height: rect.height };
      place(rect.left, rect.top);
    });
  }

  // 13. 现代化API滚动事件监听 (使用 IntersectionObserver)

  // 定义目录中的链接，用于添加高亮类名
  const tocLinks = tocList.querySelectorAll('a');

  // 创建交叉观察器实例，回调函数会在被观察元素进入或离开视口（或达到指定可见度阈值）时触发
  const observer = new IntersectionObserver(
    (entries) => {
      // 筛选出所有可见且达到阈值的标题
      const visibleEntries = entries.filter(
        (entry) => entry.isIntersecting && entry.intersectionRatio >= 0.1
      );

      // 不满足返回
      if (visibleEntries.length === 0) return;

      // 按文档顺序排序：boundingClientRect.top 越小越靠上
      visibleEntries.sort(
        (a, b) => a.boundingClientRect.top - b.boundingClientRect.top
      );

      // 取最靠上的标题作为当前激活项;
      const currentId = visibleEntries[0].target.id;

      // 遍历所有的目录链接，更新高亮状态
      tocLinks.forEach((link) => {
        // 首先移除所有链接的 'active' 高亮类名，确保状态重置
        link.classList.remove('active');

        // 检查当前链接的 href 属性是否指向刚才确定的 currentId
        // 例如：如果 currentId 是 'section-1'，则查找 href 为 '#section-1' 的链接
        if (link.getAttribute('href') === `#${currentId}`) {
          // 如果匹配，则为该链接添加 'active' 类名，实现高亮显示
          link.classList.add('active');
        }
      });
    },
    {
      // 配置选项
      rootMargin: '-1% 0px -94% 0px', // 关键：仅观察视口顶部 1%~6% 的区域
      threshold: [0.1], // 触发回调的可见度阈值数组
      // 当元素可见比例达到 10% 时，回调函数都会被触发
      // 这有助于更灵敏地捕捉元素进入视口的瞬间
    }
  );

  // 14. 遍历所有需要监听的标题元素 (headers)
  headers.forEach((header) => {
    // 将每个标题元素加入观察队列
    observer.observe(header);
  });

  // 15. 目录项的点击滚动不再在这里处理。
  // 站内锚点（含目录、评论锚点）已由 smooth_scroll.js 统一接管，
  // 两边各自绑定会导致同一次点击触发两次滚动。

  // 16. 监听按钮点击事件，实现目录关闭
  getById('tocCloseBtn').addEventListener('click', function () {
    const target = getById('post-toc');
    target.classList.toggle('d-none'); // 这个逻辑按理说应该是移除而不是转换，但是不影响
  });

  // 17. 监听按钮点击事件，实现目录打开与关闭
  tocToggleBtn.addEventListener('click', function () {
    const target = getById('post-toc');
    target.classList.toggle('d-none');
  });
});
