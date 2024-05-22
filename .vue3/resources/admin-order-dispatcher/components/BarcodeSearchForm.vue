<template>
  <form action="#" @submit.prevent="submit" autocomplete="off">
    <div class="field--input-container">
      <label class="screen-reader-text" for="input--search">Scan code or enter ShipStation ID</label>
      <input id="input--search" class="input--search" type="text" v-model="search" ref="inputEl"
             placeholder="Scan code or enter ShipStation ID">
      <ShaplaButton theme="primary">Search</ShaplaButton>
      <ShaplaButton theme="default" class="button--clear" :class="{'is-active':search.length}"
                    @click.prevent="clear">Clear
      </ShaplaButton>
    </div>
  </form>
</template>

<script lang="ts" setup>
import {ShaplaButton} from '@shapla/vue-components';
import {onMounted, ref} from "vue";

const search = ref('');
const inputEl = ref(null)
const emit = defineEmits<{
  input: [value: string];
  submit: [value: string];
}>()

const props = defineProps({
  value: {type: String, default: ''},
})

const submit = () => {
  triggerInputEvent();
  emit('submit', search.value);
}
const clear = () => {
  search.value = '';
  triggerInputEvent();
  focusInput();
}
const triggerInputEvent = () => {
  emit('input', search.value);
}
const focusInput = () => {
  if (inputEl.value) {
    inputEl.value.focus();
  }
}

onMounted(() => {
  search.value = props.value;
  setTimeout(() => focusInput(), 10)
})
</script>
