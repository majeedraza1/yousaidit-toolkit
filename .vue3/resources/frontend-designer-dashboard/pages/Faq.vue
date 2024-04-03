<template>
  <div>
    <ShaplaToggles :boxed-mode="true">
      <ShaplaToggle v-for="item in items" :key="item.id" :name="item.title">
        <div v-html="item.content"></div>
      </ShaplaToggle>
    </ShaplaToggles>
  </div>
</template>

<script lang="ts" setup>
import axios from "../../utils/axios";
import {ShaplaToggle, ShaplaToggles} from '@shapla/vue-components';
import {onMounted, ref} from "vue";

const items = ref([])

const getItems = () => {
  axios.get('designer-faqs').then(response => {
    items.value = response.data.data;
  }).catch(errors => {
    console.log(errors);
  });
}

onMounted(() => {
  getItems();
})
</script>
