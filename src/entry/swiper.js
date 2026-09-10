/* import Swiper and modules styles */
import Swiper from "swiper";
import { Autoplay, Pagination, EffectFade } from "swiper/modules";
import "swiper/css";//only core Swiper styles
//import "swiper/css/autoplay";//this is an empty file
import "swiper/css/effect-fade";
import "swiper/css/navigation";
import "swiper/css/pagination";

document.addEventListener("DOMContentLoaded", async () => {
  // 实例创建后无需保留引用（后续没有调用任何 swiper API）
  new Swiper(".swiper", {
    modules: [Pagination, Autoplay, EffectFade],
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
      type: 'bullets',
    },
    loop: true,
    autoplay: {
      delay: 2000,
      pauseOnMouseEnter: true,
      disableOnInteraction: false,
    },
    effect: 'fade',
    crossFade:true,
  });
});
