<template>
  <div class="yousaidit-admin-paypal-payouts">
    <h1 class="wp-heading-inline">PayPal Payout</h1>
    <hr class="wp-header-end">
    <div class="mb-4">
      <ShaplaButton theme="primary" size="small" @click.prevent="store.getItems">Refresh</ShaplaButton>
    </div>

    <ShaplaTabs>
      <ShaplaTab selected name="Status">
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="4" v-for="count_card in store.statuses" :key="count_card.key">
            <div>
              <ReportCard :title="count_card.label">
                {{ count_card.count }}
              </ReportCard>
            </div>
          </ShaplaColumn>
        </ShaplaColumns>
      </ShaplaTab>
      <ShaplaTab name="Payment History">
        <div class="mb-4">
          <ShaplaTablePagination
              :per-page="store.pagination.per_page"
              :current-page="store.pagination.current_page"
              :total-items="store.pagination.total_items"
          />
        </div>
        <ShaplaTable
            :show-cb="false"
            :items="store.items"
            :columns="columns"
            :actions="actions"
            @click:action="handleActionClick"
        />
        <div class="mt-4">
          <ShaplaTablePagination
              :per-page="store.pagination.per_page"
              :current-page="store.pagination.current_page"
              :total-items="store.pagination.total_items"
            />
        </div>
      </ShaplaTab>
    </ShaplaTabs>
    <div style="position: fixed;bottom:15px;right:15px;z-index: 100">
      <ShaplaButton fab theme="primary" size="large" @click="state.showModal = true">+</ShaplaButton>
    </div>
    <ShaplaModal :active="state.showModal" type="box" title="New Payout" content-class="shapla-modal-confirm"
                 :show-close-icon="false" :close-on-background-click="false" content-size="small">
      <div class="shapla-modal-confirm__content">
        <div class="shapla-modal-confirm__icon is-info">
          <div class="shapla-modal-confirm__icon-content">!</div>
        </div>
        <h3 class="shapla-modal-confirm__title">Are you sure to create a new payout?</h3>
        <div class="shapla-modal-confirm__message">
          Only designers, whom unpaid commissions (for completed orders) are more than {{ store.min_amount }}, will
          be paid.

          <p>Choose order status to pay.</p>
          <div class="text-left mt-4">
            <template v-for="info in store.statuses">
              <ShaplaCheckbox v-if="info.status" :value="info.key" v-model="store.statuses_to_pay">
                {{ info.status }}
              </ShaplaCheckbox>
            </template>
          </div>
        </div>
      </div>
      <div class="shapla-modal-confirm__actions">
        <button class="shapla-button" @click.prevent="state.showModal = false">Cancel</button>
        <button class="shapla-button is-primary" @click.prevent="store.payByWcOrderStatuses"
                :disabled="store.statuses_to_pay.length < 1"> Ok
        </button>
      </div>
    </ShaplaModal>
  </div>
</template>

<script lang="ts" setup>
import {
  ShaplaButton,
  ShaplaCheckbox,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaModal,
  ShaplaTab,
  ShaplaTable, ShaplaTablePagination,
  ShaplaTabs
} from '@shapla/vue-components';
import ReportCard from "../components/ReportCard.vue";
import useAdminDesignerPayPalPayoutStore from "../stores/paypal-payout.ts";
import {onMounted, reactive} from "vue";
import {useRouter} from "vue-router";

const store = useAdminDesignerPayPalPayoutStore();
const router = useRouter();

const state = reactive({
  showModal: false,
  min_amount: 1,
  count_cards: [],
  items: [],
})
const columns = [
  {key: 'payment_batch_id', label: 'Batch ID'},
  {key: 'payment_status', label: 'Payment Status'},
  {key: 'currency', label: 'Currency'},
  {key: 'amount', label: 'Amount'},
  {key: 'created_at', label: 'Created'},
  {key: 'updated_at', label: 'Updated'},
]
const actions = [
  {key: 'view', label: 'View'},
  {key: 'sync', label: 'Sync'},
]

const handleActionClick = (action, item) => {
  if ('sync' === action) {
    store.syncFromPayPal(item.payment_id);
  }
  if ('view' === action) {
    router.push({name: 'Payout', params: {id: item.payment_id}});
  }
}

onMounted(() => {
  store.getItems();
})
</script>
