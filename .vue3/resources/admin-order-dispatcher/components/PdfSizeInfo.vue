<template>
  <div class="w-full">
    <div class="md:flex flex-wrap -m-4" v-if="items.length">
      <template v-for="(item, key) in items" :key="`c-${key}`">
        <PrintCardBox
            :item="item"
            @need-force-refresh="$emit('need-force-refresh')"
        />
      </template>
    </div>
  </div>
</template>

<script lang="ts" setup>
import PrintCardBox from "./PrintCardBox.vue";

const props = defineProps({
  marketplace: {type: String},
  items: {type: Array, default: () => []},
})
</script>
