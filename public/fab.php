<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/* ------------------------------------------------------------------
 * 右下角悬浮按钮组：「个性化设置」+「回到顶部」
 * ------------------------------------------------------------------
 * 合成一组的原因：两者都固定在右下角，分开定位迟早互相压盖，层级也各写各的。
 * 合成后统一层高（--z-fab），回到顶部收起时只做 visibility 隐藏、仍然占位，
 * 因此设置按钮不会随着滚动出现/消失而上下跳动。
 *
 * 面板沿用「后台默认值 + 访客本机覆盖」两层模型：
 *   控件的 data-default 由 PHP 输出后台默认值，JS 只在访客改过时用
 *   localStorage 覆盖它（见 src/js/main/settings.js）。
 *   控件的可选值清单也全部由 PHP 渲染 —— 想增删档位/颜色，只改
 *   core/func.php 里的 bubbleFontSizes() / bubbleThemeColors()，JS 无需改动。
 */
$visitorDefaults = bubbleVisitorDefaults();
$fontSizes = bubbleFontSizes();
$themeColors = bubbleThemeColors();

// 字号滑条的初始档位。JS 会在初始化时立即按实际存储重算，
// 这里只是为了让标记在「脚本未执行」时也不是个非法状态。
$defaultFontSize = 'm';
$defaultFontSizeIndex = array_search($defaultFontSize, array_keys($fontSizes), true);
if ($defaultFontSizeIndex === false) {
  $defaultFontSizeIndex = 0;
}

// 回到顶部：老用户升级主题后该配置项还是 NULL，用 ?? 'on' 兜底
$showBackToTop = ($this->options->backToTop ?? 'on') === 'on';
?>
<div class="fab-group">
  <div class="fab-settings">
    <!-- 触发按钮在前、面板在后：面板在 DOM 里紧跟按钮，Tab 才能从按钮自然进入面板。
         视觉上面板靠 bottom: calc(100% + 8px) 翻到按钮上方，与 DOM 顺序无关。 -->
    <button type="button" id="settingsFab" class="fab-btn" aria-expanded="false" aria-controls="settingsPanel"
      aria-label="个性化设置">
      <svg aria-hidden="true" focusable="false">
        <use href="#icon-setting"></use>
      </svg>
    </button>

    <!-- 面板自身可能滚动（视口很矮时），必须标记 data-lenis-prevent，
         否则面板内的滚轮会被平滑滚动接管成「滚动正文」 -->
    <div id="settingsPanel" class="settings-panel" data-lenis-prevent aria-label="个性化设置">
      <div class="settings-panel__header">
        <span class="settings-panel__title">个性化设置</span>
        <button type="button" id="settingsReset" class="settings-panel__reset">恢复默认</button>
      </div>

      <!-- 外观：三态用分段控件，选中态由 JS 位移一块滑块实现 -->
      <div class="field">
        <div class="field__head">
          <span class="field__label" id="settingsLabelAppearance">外观</span>
        </div>
        <div class="segmented" role="radiogroup" aria-labelledby="settingsLabelAppearance" data-setting-key="colorScheme"
          data-default="auto">
          <span class="segmented__thumb" aria-hidden="true"></span>
          <button type="button" role="radio" aria-checked="false" data-value="auto">自动</button>
          <button type="button" role="radio" aria-checked="false" data-value="light">浅色</button>
          <button type="button" role="radio" aria-checked="false" data-value="dark">深色</button>
        </div>
      </div>

      <!-- 正文字号：四档用滑条，比四行菜单省纵向空间，拖动即可实时预览 -->
      <div class="field">
        <div class="field__head">
          <span class="field__label" id="settingsLabelFontSize">正文字号</span>
          <span class="field__value" data-role="value"></span>
        </div>
        <div class="slider" role="slider" tabindex="0" aria-labelledby="settingsLabelFontSize" aria-valuemin="0"
          aria-valuemax="<?= count($fontSizes) - 1 ?>" aria-valuenow="<?= $defaultFontSizeIndex ?>"
          data-setting-key="fontSize" data-default="<?= $defaultFontSize ?>">
          <div class="slider__rail" aria-hidden="true"></div>
          <div class="slider__track" aria-hidden="true"></div>
          <div class="slider__handle" aria-hidden="true"></div>
          <!-- 刻度同时充当「可选值」清单：滑条的可选项按 DOM 顺序取自这里，
               所以增删档位只需要改 bubbleFontSizes() -->
          <div class="slider__marks" aria-hidden="true">
            <?php foreach ($fontSizes as $value => $label): ?>
              <span data-value="<?= $value ?>"><?= $label ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- 字体来源：默认系统字体，一个字体文件都不下载；站点字体需额外下载约 460KB -->
      <div class="field">
        <div class="field__head">
          <span class="field__label" id="settingsLabelFontFamily">字体来源</span>
        </div>
        <div class="segmented" role="radiogroup" aria-labelledby="settingsLabelFontFamily" data-setting-key="fontFamily"
          data-default="<?= htmlspecialchars($visitorDefaults['fontFamily'], ENT_QUOTES, 'UTF-8') ?>">
          <span class="segmented__thumb" aria-hidden="true"></span>
          <button type="button" role="radio" aria-checked="false" data-value="system">系统</button>
          <button type="button" role="radio" aria-checked="false" data-value="site">站点</button>
        </div>
      </div>

      <!-- 主题色：色块把 theme 属性打在自己身上。
           _color.scss 里的调色板选择器已从 :root[theme=x] 提到 [theme=x]，
           于是 .swatch 直接取 var(--theme-6) 就是对应该颜色的主色，
           不需要把 12 个色值在 PHP 里再抄一遍（抄一遍就等于多一处会漂移的真源）。 -->
      <div class="field">
        <div class="field__head">
          <span class="field__label" id="settingsLabelThemeColor">主题色</span>
        </div>
        <div class="swatches" role="radiogroup" aria-labelledby="settingsLabelThemeColor" data-setting-key="themeColor"
          data-default="<?= htmlspecialchars($visitorDefaults['themeColor'], ENT_QUOTES, 'UTF-8') ?>">
          <?php foreach ($themeColors as $value => $label): ?>
            <button type="button" class="swatch" role="radio" aria-checked="false" data-value="<?= $value ?>"
              theme="<?= $value ?>" title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
              aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"></button>
          <?php endforeach; ?>
        </div>
      </div>

      <p class="settings-panel__note">设置只保存在本机浏览器，不会影响其他访客。</p>
    </div>
  </div>

  <?php if ($showBackToTop): ?>
    <button type="button" id="backToTop" class="fab-btn back-to-top" aria-label="回到顶部">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M12 4l8 8h-5v8H9v-8H4z" />
      </svg>
    </button>
  <?php endif; ?>
</div>
