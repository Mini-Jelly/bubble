<?php
if (!defined('__TYPECHO_ROOT_DIR__'))
  exit;

/* 核心文件 */
require_once("core/func.php");/* 主题公共函数 */
require_once("core/config.php");/* 主题设置 */
require_once("core/fields.php");/* 自定义字段 */

//调用函数记录内存
memoryUsageRecord();
