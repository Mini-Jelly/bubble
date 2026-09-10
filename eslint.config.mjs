import js from "@eslint/js";
import globals from "globals";
import { defineConfig } from "eslint/config";

export default defineConfig([
  {
    ignores: [
      "**/node_modules",
      "**/dist",
      "**/webpack",
      "**/.git",
      "src/js/lib/**" // 第三方库（如 prism），不参与 lint
    ],
  },
  {
    files: ["src/**/*.{js,mjs,cjs}"],
    plugins: { js },
    extends: [js.configs.recommended],
  },
  {
    files: ["src/**/*.js"],
    languageOptions: {
      sourceType: "module",
    },
  },
  {
    files: ["src/**/*.{js,mjs,cjs}"],
    languageOptions: {
      globals: globals.browser,
    },
  },
  {
    // 添加规则配置
    rules: {
      // 强制变量和属性名使用 camelCase
      "camelcase": [
        "error",
        {
          properties: "always",         // 检查对象属性
          ignoreDestructuring: false,
          ignoreImports: false,
        },
      ],
      // 可选：限制标识符最小长度
      "id-length": [
        "warn",
        // e 为事件对象，a / b 为排序比较器参数，均为惯用短名
        { min: 2, exceptions: ["i", "j", "x", "y", "e", "a", "b"] },
      ],
      // 命名风格：小写开头的驼峰，或大写开头（类名 / 全大写常量）
      "id-match": [
        "error",
        "^([a-z][a-zA-Z0-9]*|[A-Z][A-Za-z0-9]*)$",
        {
          // 属性名不检查：DOM 与第三方 API 的既有命名（如 innerHTML）无法约束
          properties: false,
          onlyDeclarations: true,
          // 解构出来的名字来自外部 API（如 swiper 的 Pagination），不做约束
          ignoreDestructuring: true,
        },
      ],
    },
  },
]);