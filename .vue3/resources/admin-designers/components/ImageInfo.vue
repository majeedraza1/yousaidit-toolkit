<script setup lang="ts">
import {PropType} from "vue";
import {ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";

interface ImageInfoInterface {
  id: number,
  src: string,
  width: number,
  height: number,
}

defineProps({
  image: {type: Object as PropType<ImageInfoInterface>}
})
</script>

<template>
  <div class="bg-gray-100 p-4 rounded">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="3">Image ID</ShaplaColumn>
      <ShaplaColumn :tablet="9">{{ image.id }}</ShaplaColumn>
      <ShaplaColumn :tablet="3">Image Width (px)</ShaplaColumn>
      <ShaplaColumn :tablet="9">{{ image.width }}
      </ShaplaColumn>
      <ShaplaColumn :tablet="3">Image Height (px)</ShaplaColumn>
      <ShaplaColumn :tablet="9">{{ image.height }}
      </ShaplaColumn>
      <ShaplaColumn :tablet="3">Image URL</ShaplaColumn>
      <ShaplaColumn :tablet="9"><a :href="image.src" target="_blank"> {{ image.src }} </a></ShaplaColumn>
    </ShaplaColumns>
  </div>
</template>
