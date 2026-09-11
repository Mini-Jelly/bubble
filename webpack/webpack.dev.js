const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
  mode: "development",
  entry: {
    main: "./src/entry/main.js",
    theme_config: "./src/entry/theme_config.js",
    swiper: "./src/entry/swiper.js",
  },
  output: {
    path: path.resolve(__dirname, "../dist"),
    filename: "[name].min.js", // 输出的 JS 文件名
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
        // 见 webpack.prod.js 同处注释：CSS 导入是副作用导入，不能被摇树
        sideEffects: true,
        use: [MiniCssExtractPlugin.loader, "css-loader"],
      },
      {
        test: /\.s[ac]ss$/,
        sideEffects: true,
        use: [MiniCssExtractPlugin.loader, "css-loader", "sass-loader"],
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
      filename: "../dist/[name].min.css", // 提取的 CSS 文件名
    }),
  ],
  devtool: "source-map", // 生成 SourceMap，方便调试
  // 开发服务器
  devServer: {
    host: "localhost", // 启动服务器域名
    port: 3000, // 启动服务器端口号
    open: true, // 是否自动打开浏览器
  },
};
