// settings.js 用于nav.php中的设置按钮，能够调节主题颜色和字体大小
// (按理说应该放在nav中，无奈代码太长了)
import { getById, setHtml } from './global.js';

// 使用配置对象SETTINGS
const SETTINGS = {
  theme: {
    options: [{ value: 'auto' }, { value: 'light' }, { value: 'dark' }],
    default: 'auto',
    storageKey: 'theme',
  },
  fontSize: {
    options: [{ value: 's' }, { value: 'm' }, { value: 'l' }, { value: 'xl' }],
    default: 'm',
    storageKey: 'font-size',
  },
};

class SettingsManager {
  constructor() {
    this.toggleBtn = getById('settings-btn');
    this.menu = getById('settings-menu');
    if (!this.menu) return;

    this.init();
  }

  init() {
    // 读取配置对象，并应用所有初始设置
    Object.entries(SETTINGS).forEach(([key, config]) => {
      const stored = localStorage.getItem(config.storageKey);
      const value = stored || config.default;

      // ✅ 现在可以安全地为所有设置项调用 applySetting（即使 fontSize 不改 UI，也会激活菜单项）
      this.applySetting(key, value, false); // false 表示不保存到 localStorage（因为是初始化）
    });

    // 绑定菜单点击事件（事件委托）
    this.menu.addEventListener('click', (e) => {
      const listItem = e.target.closest("li[role='menuitem']");
      if (!listItem) return;
      const value = listItem.dataset.value;
      const settingKey = this.getSettingKeyFromValue(value);
      if (!settingKey) return;

      this.applySetting(settingKey, value);
    });

    // 监听系统主题变化（仅 auto 模式生效）
    window
      .matchMedia('(prefers-color-scheme: dark)')
      .addEventListener('change', () => {
        if (localStorage.getItem('theme') === 'auto') {
          this.applySetting('theme', 'auto');
        }
      });
  }

  getSettingKeyFromValue(value) {
    for (const [key, config] of Object.entries(SETTINGS)) {
      if (config.options.some((opt) => opt.value === value)) {
        return key; // 返回找到的设置项的键名，例如 'theme'
      }
    }
    return null;
  }

  applySetting(settingKey, value, saveToStorage = true) {
    const config = SETTINGS[settingKey];
    if (!config) return;

    const option = config.options.find((opt) => opt.value === value);
    if (!option) return;

    // 只更新属于当前 settingKey 的菜单项
    const relevantItems = this.menu.querySelectorAll(
      `li[role='menuitem'][data-setting-key="${settingKey}"]`
    );

    relevantItems.forEach((item) => {
      item.classList.toggle('active', item.dataset.value === value);
    });

    // 应用实际效果（如 theme）
    if (settingKey === 'theme') {
      // 解析最终要应用的主题
      let finalTheme = value;
      // 如果用户选择了自动调整主题颜色
      if (value === 'auto') {
        // 判断用户电脑是否开启了深色模式
        finalTheme = window.matchMedia('(prefers-color-scheme: dark)').matches
          ? 'dark'
          : 'light';
      }
      // 设置属性到HTML上
      setHtml('color-scheme', finalTheme);
      // 是否保存到本地？方便下次访问时使用上次的配置
      if (saveToStorage) {
        localStorage.setItem(config.storageKey, value);
      }
    } else if (settingKey === 'fontSize') {
      // 设置字体大小
      setHtml('font-size-mode', value);

      // 是否保存到本地？方便下次访问时使用上次的配置
      if (saveToStorage) {
        localStorage.setItem(config.storageKey, value);
      }
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // 1. 初始化设置管理器
  const settingsManager = new SettingsManager();

  const nav = getById('nav');

  // 获取按钮和下拉菜单
  const settingsBtn = nav.querySelector('.settings-btn');
  const settingsMenu = nav.querySelector('.settings-menu');

  // --- 修改：使用更好的下拉菜单控制方式 ---
  // 为按钮添加 aria-expanded 属性，以符合无障碍标准
  const updateAriaExpanded = () => {
    const isExpanded = settingsMenu.classList.contains('show');
    settingsBtn.setAttribute('aria-expanded', isExpanded);
  };

  // 当按钮被点击时，切换菜单的显示/隐藏状态
  settingsBtn.addEventListener('click', function (e) {
    e.stopPropagation(); // 阻止事件冒泡到 window.click 监听器
    settingsMenu.classList.toggle('show');
    updateAriaExpanded();
  });

  // 点击菜单本身时，不要关闭菜单
  settingsMenu.addEventListener('click', function (e) {
    e.stopPropagation();
  });

  // 点击菜单外部区域时，隐藏菜单
  window.addEventListener('click', function () {
    if (settingsMenu.classList.contains('show')) {
      settingsMenu.classList.remove('show');
      updateAriaExpanded();
    }
  });

  // 按下 Escape 键时关闭菜单
  window.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && settingsMenu.classList.contains('show')) {
      settingsMenu.classList.remove('show');
      updateAriaExpanded();
    }
  });
});
