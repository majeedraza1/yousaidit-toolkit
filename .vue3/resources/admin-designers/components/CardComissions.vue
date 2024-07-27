<script setup lang="ts">
import useAdminDesignerCardStore, {SingleCommissionServerResponseInterface} from "../stores/card-store.ts";
import {ShaplaButton, ShaplaImage, ShaplaModal, ShaplaTable, ShaplaTablePagination} from "@shapla/vue-components";
import {DesignerCommissionInterface} from "../../interfaces/designer-commission.ts";
import {reactive} from "vue";
import {convertPXtoMM} from "../../utils/helper.ts";

interface CardCommissionsStateInterface {
  openModal: bool;
  activeCommission: SingleCommissionServerResponseInterface
}

const store = useAdminDesignerCardStore();

const state = reactive<CardCommissionsStateInterface>({
  openModal: false,
  activeCommission: {commission: null, dynamic_card_payload: null, wc_order_exists: false}
})

const onPaginate = (nextPage: number) => {
  store.getCardCommissions(store.card.id, nextPage)
}

const onCloseModal = () => {
  state.openModal = false;
  state.activeCommission = {commission: null, dynamic_card_payload: null, wc_order_exists: false}
}

const openLinkOnBlank = (link: string) => {
  const anchorElement = document.createElement('a');
  anchorElement.target = '_blank';
  anchorElement.href = link;
  anchorElement.click();
  anchorElement.remove();
}

const onActionClick = (action: 'view_order' | 'view_pdf' | 'view_design', item: DesignerCommissionInterface) => {
  if (action === 'view_design') {
    store.getCardCommission(item.commission_id).then(data => {
      state.activeCommission = data;
      state.openModal = true;
    })
    return;
  }
  if ('view_pdf' === action) {
    openLinkOnBlank(item.pdf_url)
  }
  if ('view_order' === action) {
    openLinkOnBlank(item.order_edit_url)
  }
}
</script>

<template>
  <ShaplaTablePagination
      :total-items="store.commissionsPagination.total_items"
      :per-page="store.commissionsPagination.per_page"
      :current-page="store.commissionsPagination.current_page"
      @paginate="onPaginate"
  />
  <div class="my-2">
    <ShaplaTable
        :columns="[
          {key:'order_item_id',label:'ShipStation Order Item Id'},
          {key:'order_id',label:'ShipStation Order Id'},
          {key:'wc_order_item_id',label:'Order Item Id'},
          {key:'wc_order_id',label:'Order Id'},
      ]"
        :items="store.commissions"
        :actions="[
          {key:'view_order',label:'View Order'},
          {key:'view_pdf',label:'View PDF'},
          {key:'view_design',label:'Design View'},
      ]"
        @click:action="onActionClick"
    />
  </div>
  <ShaplaTablePagination
      :total-items="store.commissionsPagination.total_items"
      :per-page="store.commissionsPagination.per_page"
      :current-page="store.commissionsPagination.current_page"
      @paginate="onPaginate"
  />

  <ShaplaModal v-if="state.openModal" :active="true" title="Preview" @close="onCloseModal" content-size="large">
    <div class="dynamic-card-canvas-container max-w-[600px] min-h-[300px] mx-auto bg-white">
      <ShaplaImage :width-ratio="154" :height-ratio="156" class="dynamic-card-canvas-image-container">
        <dynamic-card-canvas
            :data-options="`${JSON.stringify(state.activeCommission.dynamic_card_payload)}`"
            :card-width-mm="154"
            :card-height-mm="156"
            :element-width-mm="`${convertPXtoMM(600)}`"
            :element-height-mm="`${convertPXtoMM(608)}`"
            :element-width-px="600"
            :element-height-px="608"
        ></dynamic-card-canvas>
      </ShaplaImage>
    </div>
    <template v-slot:foot>
      <a :href="state.activeCommission.commission.order_edit_url" target="_blank" class="shapla-button is-primary is-outline is-small">Open Order</a>
      <a :href="state.activeCommission.commission.pdf_url" target="_blank" class="shapla-button is-secondary is-outline is-small">Debug PDf</a>
    </template>
  </ShaplaModal>
</template>
