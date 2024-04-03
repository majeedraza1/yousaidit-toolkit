<template>
  <div class="yousaidit-designer-payment">
    <h2 class="yousaidit-designer-dashboard__section-title">Payment Details</h2>
    <ShaplaTable
        :show-cb="false"
        :items="items"
        :columns="columns"
    >
      <template v-slot:commission_ids="data">
        {{ data.row.commission_ids.toString() }}
      </template>
    </ShaplaTable>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaTable} from '@shapla/vue-components';
import axios from "../../utils/axios";
import {Spinner} from "@shapla/vanilla-components";
import {onMounted, ref} from "vue";

const columns = [
  {key: 'paypal_email', label: 'Email'},
  {key: 'created_at', label: 'Payment Date'},
  {key: 'transaction_status', label: 'Payment Status'},
  {key: 'commission_ids', label: 'Revenue ID(s)'},
  {key: 'total_commissions', label: 'Total Earning', numeric: true},
];

const items = ref([])

const getItems = () => {
  Spinner.show();
  axios.get('designer-payments').then(response => {
    Spinner.hide();
    let data = response.data.data;
    items.value = data.items;
  }).catch(errors => {
    Spinner.hide();
    console.log(errors);
  });
}

onMounted(() => {
  getItems();
})
</script>
