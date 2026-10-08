// swiper.js — initializes the homepage hero slider
// Requires the Swiper library <script> to be loaded BEFORE this file.

const heroSwiper = new Swiper(".slider-container", {
  loop: true,
  speed: 800,
  autoplay: {
    delay: 4000,
    disableOnInteraction: false,
  },
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
});
