<template>
  <div>
    <EmojiPicker :native="true" @select="onSelectEmoji"/>
  </div>
</template>

<script lang="ts" setup>
import EmojiPicker from 'vue3-emoji-picker';

interface EmojiOptionInterface {
  i: string;
}

const emit = defineEmits<{
  select: [value: EmojiOptionInterface]
}>()

const onSelectEmoji = (emoji: EmojiOptionInterface) => emit('select', emoji)
</script>
