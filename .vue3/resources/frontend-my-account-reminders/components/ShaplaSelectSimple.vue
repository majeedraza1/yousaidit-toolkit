<script setup lang="ts">
import {onMounted, reactive, watch} from "vue";

const emit = defineEmits<{
  "update:modelValue": [value: string | number];
}>()
const props = defineProps({
  modelValue: {type: [String, Number]},
  label: {type: String},
  options: {type: Array, default: () => []},
  labelKey: {type: String, default: 'label'},
  valueKey: {type: String, default: 'value'},
})

const state = reactive<{
  value: string | number;
}>({
  value: '',
})

watch(() => props.modelValue, newValue => state.value = newValue)
watch(() => state.value, newValue => emit('update:modelValue', newValue))

onMounted(() => {
  state.value = props.modelValue;
})
</script>

<template>
  <div class="shapla-select-simple shapla-text-field" :class="{'has-value':modelValue}">
    <select v-model="state.value" class="shapla-text-field__select">
      <option
          v-for="_group in options"
          :value="_group[valueKey]"
          :key="_group[valueKey]"
      >{{ _group[labelKey] }}
      </option>
    </select>
    <label class="shapla-text-field__label" v-html="label"/>
  </div>
</template>

<style scoped lang="scss">
@use "shapla-css/src/index" as shapla;

.shapla-select-simple {
  position: relative;
  border: 1px solid shapla.$border-color;

  select {
    border: 1px solid rgba(0, 0, 0, 0.12);
    margin-bottom: 0;
    padding-bottom: 0;
    height: 3em;
  }

  &.has-value {
    label {
      font-size: .75rem;
      top: .25rem;
    }
  }
}


</style>