<script setup lang="ts">
import {PropType} from "vue";
import {ArtWorkProductInterface} from "../../interfaces/art-work.ts";
import PdfCardInfo from "@/admin/order-dispatcher/components/PdfCardInfo.vue";

defineProps({
  product: {type: Object as PropType<ArtWorkProductInterface>}
})
</script>

<template>
  <div v-if="product.product_sku" class="artwork-info flex items-center justify-start my-1 space-x-2">
    <a v-if="product.edit_product_url" class="artwork-info__title" :href="product.edit_product_url"
       :title="product.title">
      {{ product.title }}
    </a>
    <span v-if="!product.edit_product_url" class="artwork-info__title text-red-600" :title="product.title">
      {{ product.title }}
    </span>
    <span class="artwork-info__sku">({{ product.product_sku }})</span>
    <span class="artwork-info__qty">(Qty: {{ product.quantity }})</span>
    <a class="artwork-info__icon-pdf" target="_blank" v-if="product.art_work.url" :href="product.art_work.url">
      <pdf-card-info :info="product.art_work"/>
    </a>
    <span class="artwork-info__icon-chat" v-if="product.has_inner_message" :title="product.inner_message">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path fill="none" d="M0 0h24v24H0z"/>
				<path
            d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
			</svg>
		</span>
  </div>
</template>
