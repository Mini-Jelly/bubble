<!-- 加载所需css和js文件 -->
<link rel="shortcut icon" href="<?php $this->options->themeUrl('assets/img/favicon/favicon.svg'); ?>"
  type="image/x-icon">
<script src="<?php $this->options->themeUrl('dist/lazysizes.min.js'); ?>"></script>
<link rel="stylesheet" href="<?php $this->options->themeUrl('dist/main.min.css'); ?>">
<script src="<?php $this->options->themeUrl('dist/main.min.js'); ?>"></script>
<?php if ($this->options->swiper) { ?>
  <script src="<?php $this->options->themeUrl('dist/swiper.min.js'); ?>"></script>
  <link rel="stylesheet" href="<?php $this->options->themeUrl('dist/swiper.min.css'); ?>">
<?php } ?>
<!-- TODO 国内的cdn太垃圾了，暂时不做cdn -->
