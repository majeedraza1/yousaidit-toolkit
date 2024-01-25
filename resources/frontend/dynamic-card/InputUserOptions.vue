<script lang="ts">
import {defineComponent} from 'vue'
import SvgIcon from "@/components/DynamicCardGenerator/SvgIcon.vue";
import {inputSlider} from "shapla-vue-components";

const defaultUserOptions = () => {
  return {
    rotate: 0,
    zoom: 0,
    position: {top: 0, left: 0},
  }
}

export default defineComponent({
  name: "InputUserOptions",
  components: {SvgIcon, inputSlider},
  model: {prop: 'value', event: 'change'},
  props: {
    value: {type: Object, default: () => defaultUserOptions()},
    cardWidthMm: {type: Number, default: 0},
    cardHeightMm: {type: Number, default: 0},
  },
  data() {
    return {
      userOptions: defaultUserOptions()
    }
  },
  watch: {
    value: {
      handler(newValue) {
        this.userOptions = newValue;
      },
      deep: true
    },
    userOptions: {
      handler(newValue) {
        this.$emit('change', newValue);
      },
      deep: true
    }
  },
  methods: {
    emitChange() {
      this.$emit('change', this.userOptions);
    },
    updateRotate(rotate) {
      const factor = 5;
      if ('right' === rotate) {
        const newValue = (this.userOptions.rotate + factor);
        this.userOptions.rotate = newValue > 180 ? 180 : newValue;
      }
      if ('left' === rotate) {
        const newValue = (this.userOptions.rotate - factor)
        this.userOptions.rotate = newValue < -180 ? -180 : newValue;
      }
    },
    updateZoom(zoom) {
      const factor = 5;
      if ('in' === zoom) {
        this.userOptions.zoom = (this.userOptions.zoom + factor);
      }
      if ('out' === zoom) {
        this.userOptions.zoom = (this.userOptions.zoom - factor);
      }
    },
    updatePosition(direction) {
      const factor = 5;
      const position = this.userOptions.position;
      if ('bottom' === direction) {
        this.userOptions.position.top = position.top + factor;
      }
      if ('top' === direction) {
        this.userOptions.position.top = position.top - factor
      }
      if ('right' === direction) {
        this.userOptions.position.left = position.left + factor
      }
      if ('left' === direction) {
        this.userOptions.position.left = position.left - factor
      }
    }
  }
})
</script>

<template>
  <div>
    <div>
      <div class="flex font-bold mb-1"> Rotate</div>
      <div class="flex space-x-2">
        <svg-icon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="rotate-left" hoverable
                  size="large" @click="()=>updateRotate('left')"/>
        <div class="flex-shrink flex-grow py-4">
          <input-slider :min="-180" :max="180" :step="5" :show-reset="false" :show-input="false"
                        v-model="userOptions.rotate"/>
        </div>
        <svg-icon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="rotate-right" hoverable
                  size="large" @click="()=>updateRotate('right')"/>
      </div>
    </div>
    <div>
      <div class="flex font-bold mb-1">Zoom</div>
      <div class="flex space-x-2">
        <svg-icon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="zoom-out" hoverable
                  size="large" @click="()=> updateZoom('out')"/>
        <div class="flex-shrink flex-grow">
          <input type="number" :value="userOptions.zoom" min="-50" max="100" step="5"
                 @input="event => userOptions.zoom = parseInt(event.target.value)"
          >
        </div>
        <svg-icon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="zoom-in" hoverable
                  size="large" @click="()=> updateZoom('in')"/>
      </div>
    </div>
    <div>
      <div class="flex font-bold mb-1">Position</div>
      <div>
        <div class="flex space-x-2">
          <svg-icon class="flex-shrink-0 rotate-90 border border-solid border-gray-300 rounded-full"
                    icon="navigation-next" hoverable size="large"
                    @click="()=>updatePosition('bottom')"/>
          <div class="flex-shrink flex-grow">
            <input type="number" v-model="userOptions.position.top" step="1"
                   @input="event => userOptions.position.top = parseInt(event.target.value)">
          </div>
          <svg-icon class="flex-shrink-0 -rotate-90 border border-solid border-gray-300 rounded-full"
                    icon="navigation-next" hoverable size="large"
                    @click="()=>updatePosition('top')"/>
        </div>
        <div class="flex space-x-2">
          <svg-icon class="flex-shrink-0 rotate-180 border border-solid border-gray-300 rounded-full"
                    icon="navigation-next" hoverable size="large"
                    @click="()=>updatePosition('left')"/>
          <div class="flex-shrink flex-grow">
            <input type="number" v-model="userOptions.position.left" step="1"
                   @input="event => userOptions.position.left = parseInt(event.target.value)">
          </div>
          <svg-icon class="flex-shrink-0 border border-solid border-gray-300 rounded-full" icon="navigation-next"
                    hoverable size="large" @click="()=>updatePosition('right')"/>
        </div>
      </div>
    </div>
  </div>
</template>
