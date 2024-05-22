<template>
  <div class="pdf-image-item" :class="{'is-multiple':isMultiple}">
    <div class="pdf-image-item__items">
      <template v-if="isMultiple && Array.isArray(images)">
        <div class="pdf-image-item__item" v-for="(_img,index) in images" :key="index">
          <ShaplaImage :width-ratio="_img[widthKey]" :height-ratio="_img[heightKey]">
            <img :src="_img[urlKey]" alt="">
          </ShaplaImage>
        </div>
      </template>
      <template v-if="!isMultiple">
        <div class="pdf-image-item__item">
          <ShaplaImage :width-ratio="images[widthKey]" :height-ratio="images[heightKey]">
            <img :src="images[urlKey]" alt="">
          </ShaplaImage>
        </div>
      </template>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaImage} from '@shapla/vue-components';

defineProps({
  isMultiple: {type: Boolean, default: true},
  images: {type: [Array, Object], default: () => []},
  urlKey: {type: String, default: 'url'},
  widthKey: {type: String, default: 'width'},
  heightKey: {type: String, default: 'height'},
})
</script>
