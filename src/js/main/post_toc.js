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
  tocContainer.innerHTML = `<div id="post-toc-header" class="post-toc-header">
    <h2>文章目录</h2>
    <button id="tocCloseBtn" class="post-toc-close-btn">❌</button>
    </div>
    <ul id="toc-list"></ul>`;

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

  // 12. 目录可拖拽，仅设置标题区域，防止和css中的resize冲突
  // 方案1：
  enableDrag(tocContainer, getById('post-toc-header'));
  // 方案2：enableDrag(tocContainer,tocContainer.querySelector('#post-toc-header'));

  /**
   * 可拖拽函数实现
   * 参数：需要移动的容器，可拖拽的区域
   */
  function enableDrag(el, handle) {
    let offset = { x: 0, y: 0 };

    // 如果未传递handle参数，则使用容器本身触发拖拽监听
    const dragHandle = handle || el;

    const onMouseMove = (e) => {
      el.style.left = e.clientX - offset.x + 'px';
      el.style.top = e.clientY - offset.y + 'px';
    };

    const onMouseUp = () => {
      document.removeEventListener('mousemove', onMouseMove);
      document.removeEventListener('mouseup', onMouseUp);
    };

    // 监听对应容器的鼠标事件
    dragHandle.addEventListener('mousedown', (e) => {
      if (e.target.tagName === 'A') return; // 点击链接时不触发拖拽
      offset = {
        x: e.clientX - el.offsetLeft,
        y: e.clientY - el.offsetTop,
      };
      e.preventDefault(); // 防止选中文本

      // 只在拖拽期间挂载监听，松手立刻解绑。
      // 原先挂在 document 上且永不移除，等于全站每次 mousemove 都要回调一次。
      document.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);
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

  // 15. 监听目录点击事件，实现平滑滚动
  tocList.addEventListener('click', (e) => {
    if (e.target.tagName === 'A') {
      e.preventDefault();
      const targetId = e.target.getAttribute('href');
      const targetElement = document.querySelector(targetId);
      if (!targetElement) return;

      // 使用 getBoundingClientRect 计算精确位置，兼容 fixed 定位容器
      const elementTop =
        targetElement.getBoundingClientRect().top + window.scrollY;

      window.scrollTo({
        top: elementTop - 20,
        behavior: 'smooth',
      });
    }
  });

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
