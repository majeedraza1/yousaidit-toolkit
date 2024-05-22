<script setup lang="ts">
import {PropType} from "vue";
import ArtWorkItem from "./ArtWorkItem.vue";
import {ArtWorkOrderInterface} from "../../interfaces/art-work.ts";

defineProps({
  item: {type: Object as PropType<ArtWorkOrderInterface>}
})
</script>

<template>
  <div>
    <ArtWorkItem
        v-for="_product in item.products"
        :key="_product.shipstation_item_id"
        :product="_product"
    />
  </div>
</template>
