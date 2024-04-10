<template>
  <div v-if="item"
       class="yousaidit-designer-card flex flex-col h-full relative shadow border border-solid border-gray-100">
    <div class="yousaidit-designer-card__image">
      <ShaplaImage :width-ratio="item.product_thumbnail.width" :height-ratio="item.product_thumbnail.height">
        <img class="yousaidit-designer-profile-card__image" :src="item.product_thumbnail.url"
             :alt="item.product_thumbnail.title">
      </ShaplaImage>
    </div>
    <div class="yousaidit-designer-card__info px-2">
      <div class="font-bold text-lg mb-2">{{ item.card_title }}</div>
      <div class="flex mb-2">
        <div class="w-1/3 flex items-center space-x-1 font-bold">Status:</div>
        <div class="w-2/3">
          <div :class="`yousaidit-designer-card__status status-${item.status} rounded px-2 py-1 leading-0`">
            {{ item.status }}
          </div>
        </div>
      </div>
      <div class="flex mb-2" v-if="item.product_id">
        <div class="w-1/3 flex items-center space-x-1">
          <strong>Product:</strong>
        </div>
        <div class="w-2/3">
          <a :href="item.product_url" target="_blank" class="text-primary font-bold text-sm">View</a>
        </div>
      </div>
      <div class="flex mb-2">
        <div class="w-1/3 flex items-center space-x-1">
          <ShaplaIcon>
            <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
              <use xlink:href="#icon-svg-print"></use>
            </svg>
          </ShaplaIcon>
          <strong>Sizes:</strong>
        </div>
        <div class="w-2/3">
          <ShaplaChip v-for="_size in item.sizes" small :key="_size.id">{{ _size.title }}</ShaplaChip>
        </div>
      </div>
      <div class="flex mb-2">
        <div class="w-1/3 flex items-center space-x-1">
          <ShaplaIcon>
            <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
              <use xlink:href="#icon-svg-category"></use>
            </svg>
          </ShaplaIcon>
          <strong>Category:</strong>
        </div>
        <div class="w-2/3">
          <ShaplaChip v-for="tag in item.categories" small :key="tag.id">{{ tag.title }}</ShaplaChip>
        </div>
      </div>
      <div class="flex mb-2">
        <div class="w-1/3 flex items-center space-x-1">
          <ShaplaIcon>
            <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
              <use xlink:href="#icon-svg-tags"></use>
            </svg>
          </ShaplaIcon>
          <strong>Tags:</strong>
        </div>
        <div class="w-2/3">
          <ShaplaChip v-for="tag in item.tags" :key="tag.id">{{ tag.title }}</ShaplaChip>
        </div>
      </div>
    </div>
    <div class="flex-grow"></div>
    <div class="yousaidit-designer-card__actions">
      <div class="yousaidit-designer-card__comments" @click="$emit('click:comments',item)"
           v-if="item.comments_count >0">
        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
          <path
              d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
          <path d="M0 0h24v24H0z" fill="none"/>
        </svg>
      </div>
      <div class="yousaidit-designer-card__settings" @click="$emit('click:settings',item)"
           v-if="item.status === 'accepted'">
        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24"
             viewBox="0 0 24 24"
             width="24">
          <g>
            <path d="M0,0h24v24H0V0z" fill="none"/>
            <path
                d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
          </g>
        </svg>
      </div>
      <div class="yousaidit-designer-card__delete" @click="handleDeleteItem(item)"
           v-if="item.status === 'rejected'">
        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
          <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5l-1-1h-5l-1 1H5v2h14V4z"/>
          <path d="M0 0h24v24H0V0z" fill="none"/>
        </svg>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaChip, ShaplaIcon, ShaplaImage} from '@shapla/vue-components';
import {Dialog} from "@shapla/vanilla-components";
import {PropType} from "vue";
import {DesignerCardModelInterface} from "../../interfaces/designer-card.ts";

const emit = defineEmits<{
  "click:delete": [item: DesignerCardModelInterface];
  "click:comments": [item: DesignerCardModelInterface];
  "click:settings": [item: DesignerCardModelInterface];
}>()

defineProps({
  item: {type: Object as PropType<DesignerCardModelInterface>}
});

const handleDeleteItem = (item: DesignerCardModelInterface) => {
  Dialog.confirm('Are you sure to delete?').then(confirmed => {
    if (confirmed) {
      emit('click:delete', item);
    }
  })
}
</script>
