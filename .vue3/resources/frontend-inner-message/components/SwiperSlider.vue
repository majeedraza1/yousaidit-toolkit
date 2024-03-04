<template>
  <div ref="element" class="swiper">
    <div class="swiper-wrapper">
      <SwiperSliderItem :card_size="card_size" v-if="false === hideCanvas">
        <template v-slot:default="slotProps">
          <slot name="canvas" v-bind:sizes="slotProps.sizes">Design Card canvas</slot>
        </template>
      </SwiperSliderItem>
      <SwiperSliderItem :card_size="card_size">
        <slot name="video-message">Video message canvas</slot>
      </SwiperSliderItem>
      <SwiperSliderItem :card_size="card_size">
        <slot name="inner-message">Inner message canvas</slot>
      </SwiperSliderItem>
    </div>
  </div>
</template>

<script lang="ts" setup>
import 'swiper/css';
import 'swiper/css/pagination';
import Swiper from 'swiper';
import {Navigation} from 'swiper/modules';
import SwiperSliderItem from "./SwiperSliderItem.vue";
import {onMounted, ref, watch} from "vue";

const emit = defineEmits<{
  slideChange: [value: number]
}>();
const props = defineProps({
  card_size: {type: String, default: ''},
  slideTo: {type: Number, default: 0},
  hideCanvas: {type: Boolean, default: false}
});

const element = ref<HTMLDivElement>(null);
const swiper = ref(null);

onMounted(() => {
  Swiper.use([Navigation]);
  swiper.value = new Swiper(element.value, {
    navigation: {
      nextEl: element.value.querySelector<HTMLDivElement>('.swiper-button-next'),
      prevEl: element.value.querySelector<HTMLDivElement>('.swiper-button-prev'),
    },
    slidesPerView: 1,
    spaceBetween: 10,
    centeredSlides: true,
    breakpoints: {
      800: {
        slidesPerView: 'auto',
      }
    }
  });

  swiper.value.on('slideChange', event => {
    emit('slideChange', event.activeIndex)
  })
})

watch(() => props.slideTo, newValue => {
  if (swiper.value) {
    swiper.value.slideTo(newValue);
  }
})
</script>
