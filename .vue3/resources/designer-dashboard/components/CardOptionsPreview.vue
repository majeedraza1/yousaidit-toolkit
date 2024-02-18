<script setup lang="ts">
import {computed, PropType} from "vue";
import {CardOptionInterface} from "../../interfaces/designer-card.ts";
import {ShaplaChip, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import useDesignerDashboardStore from "../store.ts";

const store = useDesignerDashboardStore();

const props = defineProps({
  card: {type: Object as PropType<CardOptionInterface>},
  isMug: {type: Boolean, default: false}
})

const categories = computed(() => {
  if (props.isMug) {
    return store.mug_categories;
  }
  return store.card_categories;
})

const isAttributeSelected = (attribute, term) => {
  if (props.card.attributes[attribute.attribute_name]) {
    return props.card.attributes[attribute.attribute_name].indexOf(term.id.toString()) !== -1;
  }
  return false;
}
</script>

<template>
  <ShaplaColumns multiline v-if="Object.keys(card).length">
    <ShaplaColumn :tablet="3"><strong>Title</strong></ShaplaColumn>
    <ShaplaColumn :tablet="9">{{ card.title }}</ShaplaColumn>

    <ShaplaColumn :tablet="3"><strong>Card Sizes</strong></ShaplaColumn>
    <ShaplaColumn :tablet="9">
      <template v-for="_size in store.card_sizes">
        <ShaplaChip v-if="card.sizes.indexOf(_size.value) !== -1" :key="_size.value">
          {{ _size.label }}
        </ShaplaChip>
      </template>
    </ShaplaColumn>

    <ShaplaColumn :tablet="3">
      <strong v-if="!isMug">Card Categories</strong>
      <strong v-if="isMug">Mug Categories</strong>
    </ShaplaColumn>
    <ShaplaColumn :tablet="9">
      <template v-for="_cat in categories">
        <ShaplaChip v-if="card.categories_ids.includes(_cat.id.toString())" :key="_cat.id">
          {{ _cat.name }}
        </ShaplaChip>
      </template>
    </ShaplaColumn>

    <ShaplaColumn :tablet="3"><strong>Card Tags</strong></ShaplaColumn>
    <ShaplaColumn :tablet="9">
      <template v-for="_tag in store.card_tags">
        <ShaplaChip v-if="card.tags_ids.includes(_tag.id.toString())" :key="_tag.id"> {{ _tag.name }}</ShaplaChip>
      </template>
    </ShaplaColumn>

    <template v-for="_attr in store.card_attributes">
      <ShaplaColumn :tablet="3"><strong>{{ _attr.attribute_label }}</strong></ShaplaColumn>
      <ShaplaColumn :tablet="9">
        <template v-for="_tag in _attr.options">
          <ShaplaChip v-if="isAttributeSelected(_attr,_tag)" :key="_tag.id">{{ _tag.name }}</ShaplaChip>
        </template>
      </ShaplaColumn>
    </template>
    <slot name="before-column-end"/>
  </ShaplaColumns>
</template>
