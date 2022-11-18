window.addEventListener('load', function () {

    var swiper = new Swiper(".mySwiper", {
        slidesPerView: 1,
        loop: true,
        centeredSlides: true,
        autoplay: {
          delay: 2500,
          disableOnInteraction: false,
        },
        pagination: {
          el: ".swiper-pagination",
          clickable: true
        },
    });

    window.app = new Vue({
        el: '#app',
        components: {
        },
        data: ()=>({
          coords: [54, 39],
          settings:{
            apiKey: '',
            lang: 'ru_RU',
            coordorder: 'latlong',
            enterprise: false,
            version: '2.1'
          }
        }),
        methods: {

        }
    })

})