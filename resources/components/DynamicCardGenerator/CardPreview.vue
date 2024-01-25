<template>
  <div class="shadow-lg card-canvas" :style="canvas_styles">
    <dynamic-card-canvas
        style="width: 100%"
        v-if="dynamic_card_payload"
        :options="`${JSON.stringify(dynamic_card_payload)}`"
        :card-width-mm="card_width_in_mm"
        :card-height-mm="card_height_in_mm"
        :element-width-mm="element_width_in_mm"
        :element-height-mm="element_height_in_mm"
    ></dynamic-card-canvas>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";

export default defineComponent({
  name: "CardPreview",
  props: {
    card_size: {type: String},
    card_sizes: {type: Array},
    canvas_width: {type: [String, Number]},
    dynamic_card_payload: {type: Object},
  },
  computed: {
    canvas_styles() {
      return {
        'width': `${this.canvas_width}px`
      }
    },
    card_size_in_mm() {
      let size = this.card_sizes.find(item => item.value === this.card_size);
      if (size) {
        return {width: (size.width / 2) + 1, height: size.height};
      }
      return {width: 0, height: 0};
    },
    card_width_in_mm() {
      return this.card_size_in_mm.width;
    },
    card_height_in_mm() {
      return this.card_size_in_mm.height;
    },
    element_width_in_mm() {
      return this.px_to_mm(this.canvas_width);
    },
    element_height_in_mm() {
      if (this.element_width_in_mm) {
        return Math.round((this.card_height_in_mm / this.card_width_in_mm) * this.element_width_in_mm);
      }
      return 0;
    }
  },
  methods: {
    calculateWidthAndHeight() {
      let innerEL = this.$el;
      let d = [this.card_width_in_mm, this.card_height_in_mm];

      if (document.body.offsetWidth < 1024) {
        this.width = document.body.offsetWidth - 30 || this.$el.offsetWidth;
        this.height = Math.round(this.width * (d[1] / d[0]));
      } else {
        this.height = innerEL.offsetHeight;
        this.width = Math.round(innerEL.offsetHeight * (d[0] / d[1]));
      }
    },
    px_to_mm(px) {
      return Math.round(px * 0.2645833333);
    },
  },
})
</script>
