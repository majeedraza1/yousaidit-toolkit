<script lang="ts" setup>
import {PropType, reactive, watch} from 'vue'
import {ShaplaRangeSlider} from "@shapla/vue-components";
import SvgIcon from "../../frontend-designer-dashboard/components/SvgIcon.vue";

interface UserInputOptionInterface {
  rotate: number,
  zoom: number,
  position: { top: number, left: number },
}

const emit = defineEmits<{
  change: [value: UserInputOptionInterface]
}>()
const props = defineProps({
  value: {
    type: Object as PropType<UserInputOptionInterface>,
    default: () => ({
      rotate: 0,
      zoom: 0,
      position: {top: 0, left: 0},
    })
  },
  cardWidthMm: {type: Number, default: 0},
  cardHeightMm: {type: Number, default: 0},
})

const state = reactive<{
  userOptions: UserInputOptionInterface
}>({
  userOptions: {
    rotate: 0,
    zoom: 0,
    position: {top: 0, left: 0},
  }
})

watch(() => props.value, (newValue: UserInputOptionInterface) => state.userOptions = newValue, {deep: true})
watch(() => state.userOptions, (newValue: UserInputOptionInterface) => emit('change', newValue), {deep: true})

const updateRotate = (rotate: string) => {
  const factor = 5;
  if ('right' === rotate) {
    const newValue = (state.userOptions.rotate + factor);
    state.userOptions.rotate = newValue > 180 ? 180 : newValue;
  }
  if ('left' === rotate) {
    const newValue = (state.userOptions.rotate - factor)
    state.userOptions.rotate = newValue < -180 ? -180 : newValue;
  }
}
const updateZoom = (zoom: string) => {
  const factor = 5;
  if ('in' === zoom) {
    state.userOptions.zoom = (state.userOptions.zoom + factor);
  }
  if ('out' === zoom) {
    state.userOptions.zoom = (state.userOptions.zoom - factor);
  }
}
const updatePosition = (direction: string) => {
  const factor = 5;
  const position = state.userOptions.position;
  if ('bottom' === direction) {
    state.userOptions.position.top = position.top + factor;
  }
  if ('top' === direction) {
    state.userOptions.position.top = position.top - factor
  }
  if ('right' === direction) {
    state.userOptions.position.left = position.left + factor
  }
  if ('left' === direction) {
    state.userOptions.position.left = position.left - factor
  }
}
</script>

<template>
  <div>
    <div>
      <div class="flex font-bold mb-1"> Rotate</div>
      <div class="flex space-x-2">
        <SvgIcon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="rotate-left" hoverable
                 size="large" @click="()=>updateRotate('left')"/>
        <div class="flex-shrink flex-grow py-4">
          <ShaplaRangeSlider :min="-180" :max="180" :step="5" :show-reset="false" :show-input="false"
                             v-model="state.userOptions.rotate"/>
        </div>
        <SvgIcon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="rotate-right" hoverable
                 size="large" @click="()=>updateRotate('right')"/>
      </div>
    </div>
    <div>
      <div class="flex font-bold mb-1">Zoom</div>
      <div class="flex space-x-2">
        <SvgIcon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="zoom-out" hoverable
                 size="large" @click="()=> updateZoom('out')"/>
        <div class="flex-shrink flex-grow">
          <input type="number" :value="state.userOptions.zoom" min="-50" max="100" step="5"
                 @input="event => state.userOptions.zoom = parseInt((event.target as HTMLInputElement).value)"
          >
        </div>
        <SvgIcon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="zoom-in" hoverable
                 size="large" @click="()=> updateZoom('in')"/>
      </div>
    </div>
    <div>
      <div class="flex font-bold mb-1">Position</div>
      <div>
        <div class="flex space-x-2">
          <SvgIcon class="flex-shrink-0 rotate-90 border border-solid border-gray-300 rounded-full"
                   icon="navigation-next" hoverable size="large"
                   @click="()=>updatePosition('bottom')"/>
          <div class="flex-shrink flex-grow">
            <input type="number" v-model="state.userOptions.position.top" step="1"
                   @input="event => state.userOptions.position.top = parseInt((event.target as HTMLInputElement).value)">
          </div>
          <SvgIcon class="flex-shrink-0 -rotate-90 border border-solid border-gray-300 rounded-full"
                   icon="navigation-next" hoverable size="large"
                   @click="()=>updatePosition('top')"/>
        </div>
        <div class="flex space-x-2">
          <SvgIcon class="flex-shrink-0 rotate-180 border border-solid border-gray-300 rounded-full"
                   icon="navigation-next" hoverable size="large"
                   @click="()=>updatePosition('left')"/>
          <div class="flex-shrink flex-grow">
            <input type="number" v-model="state.userOptions.position.left" step="1"
                   @input="event => state.userOptions.position.left = parseInt((event.target as HTMLInputElement).value)">
          </div>
          <SvgIcon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="navigation-next"
                   hoverable size="large" @click="()=>updatePosition('right')"/>
        </div>
      </div>
    </div>
  </div>
</template>
