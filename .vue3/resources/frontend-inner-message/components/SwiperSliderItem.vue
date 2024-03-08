<template>
  <div ref="root" class="swiper-slide p-2">
    <div :style="itemStyles" class="swiper-slide-inner shadow-xl">
      <slot v-bind:sizes="cardSizes"/>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {computed, onMounted, reactive, ref} from "vue";

const root = ref<HTMLDivElement>(null)
const availableCardSizes = window.StackonetToolkit.pdfSizes
const props = defineProps({
  card_size: {type: String, default: 'square'},
})
const state = reactive({
  height: 0,
  width: 0
})


const card_dimension = (): number[] => {
  if (Object.keys(availableCardSizes).indexOf(props.card_size) === -1) {
    return [0, 0];
  }
  let dimension = availableCardSizes[props.card_size];
  return [dimension[0] / 2, dimension[1]];
}
const cardSizes = computed(() => ({width: state.width, height: state.height}))
const itemStyles = computed(() => {
  return {
    width: `${state.width}px`,
    "--item-width": `${state.width}px`,
    "--item-height": `${state.height}px`,
  }
})

const calculateWidthAndHeight = () => {
  let innerEL = root.value.querySelector<HTMLDivElement>('.swiper-slide-inner');
  const dimension = card_dimension();

  if (document.body.offsetWidth < 1024) {
    state.width = document.body.offsetWidth - 30 || root.value.offsetWidth;
    state.height = Math.round(state.width * (dimension[1] / dimension[0]));
  } else {
    state.height = innerEL.offsetHeight;
    state.width = Math.round(state.height * (dimension[0] / dimension[1]));
  }
}

onMounted(() => {
  setTimeout(() => calculateWidthAndHeight());
  window.addEventListener('resize', () => calculateWidthAndHeight())
})
</script>

<style lang="scss" scoped>
.swiper-slide {
  width: auto;
}

.swiper-slide-inner {
  height: calc(100% - .5rem);
  margin: .25rem 0;
  overflow: hidden;
}
</style>
