<template>
  <div class="yousaidit-admin-paypal-payouts">
    <h1 class="wp-heading-inline">PayPal Payout</h1>
    <hr class="wp-header-end">
    <template v-if="store.payment">
      <div style="margin-bottom: 1rem;display: flex; justify-content: flex-end;">
        <ShaplaButton theme="primary" @click="() =>store.syncFromPayPal(store.payment.payment_id)">Sync</ShaplaButton>
      </div>
      <div style="background: #fff;padding: 16px;">
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="3">ID</ShaplaColumn>
          <ShaplaColumn :tablet="9">{{ store.payment.payment_id }}</ShaplaColumn>
        </ShaplaColumns>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="3">Payment Batch ID</ShaplaColumn>
          <ShaplaColumn :tablet="9">{{ store.payment.payment_batch_id }}</ShaplaColumn>
        </ShaplaColumns>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="3">Payment Status</ShaplaColumn>
          <ShaplaColumn :tablet="9">{{ store.payment.payment_status }}</ShaplaColumn>
        </ShaplaColumns>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="3">Currency</ShaplaColumn>
          <ShaplaColumn :tablet="9">{{ store.payment.currency }}</ShaplaColumn>
        </ShaplaColumns>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="3">Total Amount</ShaplaColumn>
          <ShaplaColumn :tablet="9">{{ store.payment.amount }}</ShaplaColumn>
        </ShaplaColumns>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="3">Created</ShaplaColumn>
          <ShaplaColumn :tablet="9">{{ store.payment.created_at }}</ShaplaColumn>
        </ShaplaColumns>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="3">Synced</ShaplaColumn>
          <ShaplaColumn :tablet="9">{{ store.payment.updated_at }}</ShaplaColumn>
        </ShaplaColumns>
      </div>

      <h2 class="title">Payout Items</h2>
      <ShaplaToggles>
        <ShaplaToggle :name="`Designer PayPal Email: ${item.paypal_email}`"
                      :subtext="`Status: ${item.transaction_status}`"
                      v-for="item in store.payment_items" :key="item.item_id">
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Item ID</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.item_id }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Designer ID</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.designer_id }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Currency</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.currency }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Total Commission Amount</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.total_commissions }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Orders IDs</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.order_ids.toString() }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Commissions IDs</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.commission_ids.toString() }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Note to PayPal</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.note }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Designer PayPal Email</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.paypal_email }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">PayPal Payout Item ID</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.payout_item_id }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">PayPal transaction status</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.transaction_status }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Note from PayPal</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ item.error_message }}</ShaplaColumn>
          </ShaplaColumns>
        </ShaplaToggle>
      </ShaplaToggles>
    </template>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaColumn, ShaplaColumns, ShaplaToggle, ShaplaToggles} from '@shapla/vue-components';
import {onMounted, ref} from "vue";
import {useRoute} from "vue-router";
import useAdminDesignerPayPalPayoutStore from "../stores/paypal-payout.ts";

const id = ref(0)
const route = useRoute();
const store = useAdminDesignerPayPalPayoutStore();

onMounted(() => {
  id.value = route.params.id;
  if (id.value) {
    store.getItem(id.value);
  }
})
</script>
