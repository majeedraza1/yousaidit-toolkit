<template>
  <div class="yousaidit-designer-profile__field">
    <div class="yousaidit-designer-profile__label" v-html="title"></div>
    <div class="yousaidit-designer-profile__value">
      <div v-if="!isEditMode">
        <template v-if="content">
          <slot name="content">{{ content }}</slot>
        </template>
        <template v-else>-</template>
      </div>
      <template v-if="isEditMode">
        <div class="yousaidit-designer-profile__input-fields" :style="`width:${fieldWidth}`">
          <slot></slot>
          <div class="yousaidit-designer-profile__actions">
            <ShaplaButton theme="primary" @click="saveData">Save</ShaplaButton>
            <ShaplaButton theme="primary" outline @click="isEditMode = !isEditMode">Cancel</ShaplaButton>
          </div>
        </div>
      </template>
    </div>
    <div class="yousaidit-designer-profile__action" v-if="!isEditMode">
      <a href="#" @click.prevent="isEditMode = !isEditMode">Edit</a>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton} from '@shapla/vue-components';
import {ref} from "vue";

const props = defineProps({
  title: {type: String},
  content: {type: String},
  fieldWidth: {type: String, default: '300px'},
})

const emit = defineEmits<{
  save: [value: string]
}>()
const isEditMode = ref(false)


const saveData = () => {
  emit('save', props.title);
  isEditMode.value = false;
}
</script>
