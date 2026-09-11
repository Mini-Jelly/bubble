import { getById, setHtml } from './global.js';

/**
 * 访客级个性化设置（右下角悬浮面板）
 *
 * 分层原则，务必保持：
 *   后台 = 站长设定的「默认值」，由 PHP 写进每个控件的 data-default
 *   前台 = 访客在本机做出的「覆盖」，存在 localStorage，优先级更高
 * 访客没动过的项一律走后台默认值、不写 localStorage；「恢复默认」= 清掉本机覆盖。
 * 所以后台改配色 / 改字体默认值，对没动过设置的访客是立刻生效的。
 *
 * 首屏不闪的那一半在 views/header.php 的内联阻塞脚本里（它必须在 CSS 生效前
 * 就把四个属性写到 <html> 上），这里只负责交互、持久化与控件同步。
 *
 * 新增一个设置项的完整步骤：
 *   1. core/config.php  加后台选项（需要站长可调默认值时）
 *   2. views/fab.php   加一个带 data-setting-key / data-default 的控件
 *   3. 本文件的 SETTINGS 里补一条「存储键 + 落到 html 的属性」
 *   4. views/header.php 的 DEFAULTS / ALLOWED 里补同一个键
 * 控件类型（分段 / 滑条 / 色块）与可取值都由 DOM 自己声明，这里不需要分支。
 */

// 设置项 → localStorage 键 / <html> 上的属性名。
// 这四个键必须与 views/header.php 的 DEFAULTS 一一对应，改一处必须改两处。
const SETTINGS = {
  colorScheme: { storageKey: 'theme', attr: 'color-scheme' },
  fontSize: { storageKey: 'font-size', attr: 'font-size-mode' },
  fontFamily: { storageKey: 'font-family', attr: 'font-family-mode' },
  themeColor: { storageKey: 'theme-color', attr: 'theme' },
};

// 与 prefers-color-scheme 的查询串。命名必须是驼峰或大驼峰（eslint id-match）
const darkMediaQuery = '(prefers-color-scheme: dark)';

// 滑条轨道的左右内缩量，必须与 _fab.scss 里 .slider__rail 的 14px 一致，
// 否则「指针落点」换算出的档位会有系统性偏移
const sliderInset = 14;

const prefersDark = () => window.matchMedia(darkMediaQuery).matches;

/* Safari 无痕模式下 localStorage 可能直接抛错，读写一律包一层，
   失败时退化为「只在本次会话生效」，而不是把整个面板搞崩 */
function readStored(key) {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
}

function writeStored(key, value) {
  try {
    localStorage.setItem(key, value);
  } catch {
    /* 忽略：存不下就只在本次会话生效 */
  }
}

function clearStored(key) {
  try {
    localStorage.removeItem(key);
  } catch {
    /* 忽略 */
  }
}

class SettingsPanel {
  constructor(panel, trigger) {
    this.panel = panel;
    this.trigger = trigger;
    this.resetBtn = getById('settingsReset');
    // key -> { el, type, values, labels, default, valueEl, current }
    this.fields = new Map();

    this.collectFields();
    this.syncAll();
    this.bind();
  }

  /**
   * 收集全部设置项。
   *
   * 可选值统一从控件内部的 [data-value] 读取：分段控件是按钮，滑条是刻度。
   * 于是「增删档位 / 增删配色」只需要改 PHP，这个文件完全不用动。
   */
  collectFields() {
    this.panel.querySelectorAll('[data-setting-key]').forEach((el) => {
      const key = el.dataset.settingKey;
      if (!SETTINGS[key]) return;

      const options = [...el.querySelectorAll('[data-value]')];
      if (options.length === 0) return;

      const type = el.classList.contains('segmented')
        ? 'segmented'
        : el.classList.contains('slider')
          ? 'slider'
          : 'swatches';

      this.fields.set(key, {
        el,
        type,
        values: options.map((node) => node.dataset.value),
        // 色块没有文字，用 aria-label（中文颜色名）兜底
        labels: options.map(
          (node) =>
            (node.textContent || '').trim() ||
            node.getAttribute('aria-label') ||
            node.dataset.value
        ),
        default: el.dataset.default || options[0].dataset.value,
        valueEl: el.closest('.field')?.querySelector('[data-role="value"]') || null,
        current: null,
      });

      // 滑块宽度 = 一格，靠这个数算出来；增删选项后无需改 CSS
      if (type === 'segmented') {
        el.style.setProperty('--seg-count', String(options.length));
      }
    });
  }

  /**
   * 把控件同步到「当前生效值」。
   *
   * 不重新写 <html> 上的属性 —— 首屏那段内联脚本已经写好了，
   * 这里再写一遍就等于多出第二份真源，两边迟早对不上。
   */
  syncAll() {
    this.fields.forEach((field, key) => {
      const stored = readStored(SETTINGS[key].storageKey);
      const value = field.values.includes(stored) ? stored : field.default;
      this.syncControl(key, value);
    });

    this.refreshResetButton();
  }

  /** 只更新控件外观与无障碍状态，不碰 <html> */
  syncControl(key, value) {
    const field = this.fields.get(key);
    if (!field) return;

    field.current = value;

    if (field.type === 'segmented') {
      const index = Math.max(field.values.indexOf(value), 0);
      field.el.style.setProperty('--seg-index', String(index));
      field.el.querySelectorAll('[data-value]').forEach((btn) => {
        btn.setAttribute('aria-checked', String(btn.dataset.value === value));
      });
      return;
    }

    if (field.type === 'slider') {
      const index = Math.max(field.values.indexOf(value), 0);
      const last = field.values.length - 1;
      const label = field.labels[index] || value;

      field.el.style.setProperty(
        '--slider-progress',
        String(last > 0 ? index / last : 0)
      );
      field.el.setAttribute('aria-valuenow', String(index));
      field.el.setAttribute('aria-valuetext', label);
      if (field.valueEl) field.valueEl.textContent = label;
      return;
    }

    field.el.querySelectorAll('[data-value]').forEach((btn) => {
      btn.setAttribute('aria-checked', String(btn.dataset.value === value));
    });
  }

  /** 把设置真正写到 <html> 上 */
  apply(key, value) {
    const config = SETTINGS[key];

    // color-scheme 只接受终值，auto 需要按系统偏好解析一次
    if (key === 'colorScheme') {
      setHtml(
        config.attr,
        value === 'auto' ? (prefersDark() ? 'dark' : 'light') : value
      );
      return;
    }

    setHtml(config.attr, value);
  }

  /**
   * 应用一次改动。
   *
   * @param {boolean} persist 是否写入 localStorage。
   *   拖滑条时会产生几十次 pointermove，逐个落盘既慢又没意义，
   *   所以拖动过程只传 false 做实时预览，松手时才落盘。
   */
  commit(key, value, persist = true) {
    const field = this.fields.get(key);
    if (!field || !field.values.includes(value)) return;

    this.syncControl(key, value);
    this.apply(key, value);

    if (persist) {
      writeStored(SETTINGS[key].storageKey, value);
      this.refreshResetButton();
    }
  }

  currentValue(key) {
    return this.fields.get(key)?.current ?? null;
  }

  /** 清掉本机全部覆盖，回到后台默认值 */
  reset() {
    this.fields.forEach((field, key) => {
      clearStored(SETTINGS[key].storageKey);
      this.commit(key, field.default, false);
    });
    this.refreshResetButton();
  }

  /** 一项覆盖都没有时，「恢复默认」按钮置灰 */
  refreshResetButton() {
    if (!this.resetBtn) return;

    const overridden = Object.keys(SETTINGS).some(
      (key) => readStored(SETTINGS[key].storageKey) !== null
    );
    this.resetBtn.disabled = !overridden;
  }

  bind() {
    // 面板内的点击全部走事件委托：控件由 PHP 渲染，将来加控件也不用改这里
    this.panel.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;

      const option = target.closest('[data-value]');
      // 滑条刻度的 data-value 只是「档位坐标」，整条滑条的拖动逻辑已经覆盖了它
      if (!option || option.closest('.slider__marks')) return;

      const field = option.closest('[data-setting-key]');
      if (!field) return;

      this.commit(field.dataset.settingKey, option.dataset.value);
    });

    if (this.resetBtn) {
      this.resetBtn.addEventListener('click', () => this.reset());
    }

    this.bindSlider();
    this.bindTrigger();

    // 访客选了「自动」时，系统主题一变就要立刻跟上
    window.matchMedia(darkMediaQuery).addEventListener('change', () => {
      if (this.currentValue('colorScheme') === 'auto') {
        this.apply('colorScheme', 'auto');
      }
    });
  }

  bindSlider() {
    const entry = [...this.fields.entries()].find(
      ([, field]) => field.type === 'slider'
    );
    if (!entry) return;

    const [key, field] = entry;
    const slider = field.el;
    const last = field.values.length - 1;

    /** 把指针横坐标换算成最近的档位 */
    const indexFromX = (clientX) => {
      const rect = slider.getBoundingClientRect();
      const usable = rect.width - sliderInset * 2;
      if (usable <= 0 || last <= 0) return 0;

      const ratio = (clientX - rect.left - sliderInset) / usable;
      return Math.min(last, Math.max(0, Math.round(ratio * last)));
    };

    let dragging = false;

    slider.addEventListener('pointerdown', (event) => {
      // 鼠标只响应左键；触屏的 button 恒为 0
      if (event.pointerType === 'mouse' && event.button !== 0) return;

      dragging = true;
      slider.classList.add('is-dragging');
      // 指针捕获：拖到面板外也不丢事件，触屏不必另外处理。
      // 指针在事件派发到手之前就失效（或事件本身是合成出来的）时这里会抛错，
      // 但拖动依赖的是后续事件的坐标换算，没有捕获也照常可用，所以吞掉即可 ——
      // 不能让它把下面的首次同步一起中断。
      try {
        slider.setPointerCapture(event.pointerId);
      } catch {
        /* 忽略：无捕获时依然可以拖动 */
      }
      event.preventDefault(); // 别在按下时顺带选中刻度文字

      this.commit(key, field.values[indexFromX(event.clientX)], false);
    });

    slider.addEventListener('pointermove', (event) => {
      if (!dragging) return;
      this.commit(key, field.values[indexFromX(event.clientX)], false);
    });

    const endDrag = (event) => {
      if (!dragging) return;

      dragging = false;
      slider.classList.remove('is-dragging');
      if (slider.hasPointerCapture(event.pointerId)) {
        slider.releasePointerCapture(event.pointerId);
      }

      // 松手才落盘
      this.commit(key, field.values[indexFromX(event.clientX)], true);
    };

    slider.addEventListener('pointerup', endDrag);
    slider.addEventListener('pointercancel', endDrag);

    // 键盘：滑条一档一档地调整，正好对应方向键与 Home / End
    slider.addEventListener('keydown', (event) => {
      const current = Math.max(field.values.indexOf(this.currentValue(key)), 0);
      let next = null;

      switch (event.key) {
        case 'ArrowLeft':
        case 'ArrowDown':
          next = current - 1;
          break;
        case 'ArrowRight':
        case 'ArrowUp':
          next = current + 1;
          break;
        case 'Home':
          next = 0;
          break;
        case 'End':
          next = last;
          break;
        default:
          return;
      }

      event.preventDefault();
      this.commit(key, field.values[Math.min(last, Math.max(0, next))]);
    });
  }

  bindTrigger() {
    const setOpen = (open, restoreFocus = false) => {
      this.panel.classList.toggle('is-open', open);
      this.trigger.setAttribute('aria-expanded', String(open));
      if (!open && restoreFocus) this.trigger.focus();
    };

    this.trigger.addEventListener('click', (event) => {
      // 不冒泡：否则 document 上的「点外部关闭」会立刻又把它关掉
      event.stopPropagation();
      setOpen(!this.panel.classList.contains('is-open'));
    });

    // 面板内部的点击同样不冒泡，否则点一下控件面板就被关掉
    this.panel.addEventListener('click', (event) => event.stopPropagation());

    document.addEventListener('click', () => {
      if (this.panel.classList.contains('is-open')) setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
      // Esc 关闭并把焦点还给触发按钮，键盘用户不会「丢失」焦点
      if (event.key === 'Escape' && this.panel.classList.contains('is-open')) {
        setOpen(false, true);
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const panel = getById('settingsPanel');
  const trigger = getById('settingsFab');
  // 主题设置里关掉回到顶部也不影响设置面板；两个元素缺一就不初始化
  if (!panel || !trigger) return;

  new SettingsPanel(panel, trigger);
});
