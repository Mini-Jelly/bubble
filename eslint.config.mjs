import js from "@eslint/js";
import globals from "globals";
import { defineConfig } from "eslint/config";

export default defineConfig([
  {
    ignores: [
      "**/node_modules",
      "**/dist",
      "**/webpack",
      "**/.git"
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
          ignoreExports: false,
        },
      ],
      // 可选：限制标识符最小长度
      "id-length": [
        "warn",
        { min: 2, exceptions: ["i", "j", "x", "y"] },
      ],
      // 可选：正则控制命名风格（更严格）
      "id-match": [
        "error",
        "^[a-z]+([A-Z][a-z]+)*$",
        {
          properties: true,
          onlyDeclarations: true,
          ignoreDestructuring: false,
        },
      ],
    },
  },
]);