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
      <div>
        <input-slider :min="0" :max="360" :step="5" :show-reset="false" v-model="userOptions.rotate"/>
      </div>
    </div>
    <div>
      <div class="flex font-bold mb-1">Zoom</div>
      <div class="flex space-x-2">
        <svg-icon class="flex-shrink-0" icon="zoom-out" hoverable @click="()=> updateZoom('out')"/>
        <div class="flex-shrink flex-grow">
          <input type="number" :value="userOptions.zoom" min="-50" max="100" step="5"
                 @input="event => userOptions.zoom = parseInt(event.target.value)"
          >
        </div>
        <svg-icon class="flex-shrink-0" icon="zoom-in" hoverable @click="()=> updateZoom('in')"/>
      </div>
    </div>
    <div>
      <div class="flex font-bold mb-1">Position</div>
      <div>
        <div class="flex space-x-2">
          <svg-icon class="flex-shrink-0" icon="arrow-downward" hoverable @click="()=>updatePosition('bottom')"/>
          <div class="flex-shrink flex-grow">
            <input type="number" v-model="userOptions.position.top" step="1"
                   @input="event => userOptions.position.top = parseInt(event.target.value)">
          </div>
          <svg-icon class="flex-shrink-0" icon="arrow-upward" hoverable @click="()=>updatePosition('top')"/>
        </div>
        <div class="flex space-x-2">
          <svg-icon class="flex-shrink-0" icon="arrow-back" hoverable @click="()=>updatePosition('left')"/>
          <div class="flex-shrink flex-grow">
            <input type="number" v-model="userOptions.position.left" step="1"
                   @input="event => userOptions.position.left = parseInt(event.target.value)">
          </div>
          <svg-icon class="flex-shrink-0" icon="arrow-forward" hoverable @click="()=>updatePosition('right')"/>
        </div>
      </div>
    </div>
  </div>
</template>
