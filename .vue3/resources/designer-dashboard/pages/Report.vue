<template>
  <div class="yousaidit-designer-dashboard">

    <div class="section--designs">
      <h2 class="yousaidit-designer-dashboard__section-title">Designs</h2>
      <ShaplaColumns multiline>
        <ShaplaColumn :tablet="6" :desktop="4" :widescreen="3" v-for="_status in cards_statuses" :key="_status.key">
          <ReportCard :title="_status.label" :content="_status.count" :background-color="_status.color"/>
        </ShaplaColumn>
      </ShaplaColumns>
    </div>

    <div class="section--revenue">
      <h2 class="yousaidit-designer-dashboard__section-title">Revenue</h2>
      <ShaplaColumns multiline>
        <ShaplaColumn :tablet="3">
          <ReportCard title="Unpaid Commission" :content="unpaid_commission" background-color="#f4d4e4"/>
        </ShaplaColumn>
        <ShaplaColumn :tablet="3">
          <ReportCard title="Paid Commission" :content="paid_commission" background-color="#dde4ff"/>
        </ShaplaColumn>
        <ShaplaColumn :tablet="3">
          <ReportCard title="Total Commission" :content="total_commission" background-color="#fdfad3"/>
        </ShaplaColumn>
        <ShaplaColumn :tablet="3">
          <ReportCard title="Total Orders" :content="total_orders" background-color="#d0ffe0"/>
        </ShaplaColumn>
      </ShaplaColumns>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaColumn, ShaplaColumns} from '@shapla/vue-components';
import ReportCard from "../components/ReportCard.vue";
import useDesignerDashboardStore from "../store";
import {computed, onMounted} from "vue";

const store = useDesignerDashboardStore();
const cards_statuses = computed(() => store.cards_statuses)
const total_commission = computed(() => store.total_commission)
const unpaid_commission = computed(() => store.unpaid_commission)
const paid_commission = computed(() => store.paid_commission)
const total_orders = computed(() => store.total_orders)

onMounted(() => {
  let user = window.DesignerProfile.user;
  if (store.designer_id!) {
    store.designer_id = user.id;
  }
  if (!Object.keys(store.designer).length) {
    store.getDesigner()
  }
})
</script>
