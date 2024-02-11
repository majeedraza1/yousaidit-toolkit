<script setup lang="ts">
defineProps({
  label: {type: String, default: ''},
  value: {type: String, default: ''},
})
</script>

<template>
  <div class="flex border-0 border-b border-dashed border-gray-300">
    <div class="w-1/3 font-bold py-2">{{ label }}</div>
    <div class="w-2/3 py-2">
      <slot>{{ value }}</slot>
    </div>
  </div>
</template>
