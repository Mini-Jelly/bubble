import { getById } from "./global.js";

document.addEventListener("DOMContentLoaded", () => {
    const postRss = getById("post-rss");
    const commentsRss = getById("comments-rss");

    // 将函数定义在使用前，并确保它是函数表达式或声明
    const fastCopy = (event) => {
        // 阻止链接跳转
        event.preventDefault();

        // 获取 href 属性
        let href = event.target.getAttribute("href");

        // 复制href到剪贴板
        try {
            navigator.clipboard.writeText(href);
            alert("RSS地址已复制到剪贴板");

        } catch (err) {
            alert("RSS地址复制失败，请手动使用右键复制");
        }
    };

    postRss.addEventListener("click", fastCopy);
    commentsRss.addEventListener("click", fastCopy);
});