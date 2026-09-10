const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

// 获取处理样式的Loaders
const getStyleLoaders = (preProcessor) => {
  return [
    MiniCssExtractPlugin.loader,
    "css-loader",
    {
      loader: "postcss-loader",
      options: {
        postcssOptions: {
          plugins: [
            "postcss-preset-env", // 能解决大多数样式兼容性问题
          ],
        },
      },
    },
    preProcessor,
  ].filter(Boolean);
};

module.exports = {
  mode: "production",
  entry: {
    main: "./src/entry/main.js",
    theme_config: "./src/entry/theme_config.js",
    swiper: "./src/entry/swiper.js",
  },
  output: {
    path: path.resolve(__dirname, "../dist"),
    filename: "[name].min.js", // 输出的 JS 文件名
    // 动态 import 切出的 chunk（如按需加载的 Prism）同样保持 .min.js 命名
    chunkFilename: "[name].min.js",
    clean: true, // 启用自动清理输出目录
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/, // 排除 node_modules
      },
      {
        test: /\.css$/,
        use: getStyleLoaders(),
      },
      {
        test: /\.s[ac]ss$/,
        use: getStyleLoaders("sass-loader"),
      },
      //处理其他资源
      {
        test: /\.(ttf|woff2?|map4|map3|avi)$/,
        type: "asset/resource",
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      // path: path.resolve(__dirname, "../dist"),
      filename: "../dist/[name].min.css", // 提取的 CSS 文件名
    }),
    // css压缩
    new CssMinimizerPlugin(),
  ],
};
