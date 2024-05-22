<template>
  <div class="yousaidit-designer-revenue">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="3">
        <report-card title="Unpaid Commission" :content="unpaid_commission" background-color="#f4d4e4"/>
      </ShaplaColumn>
      <ShaplaColumn :tablet="3">
        <report-card title="Paid Commission" :content="paid_commission" background-color="#dde4ff"/>
      </ShaplaColumn>
      <ShaplaColumn :tablet="3">
        <report-card title="Total Commission" :content="total_commission" background-color="#fdfad3"/>
      </ShaplaColumn>
      <ShaplaColumn :tablet="3">
        <report-card title="Total Orders" :content="total_orders" background-color="#d0ffe0"/>
      </ShaplaColumn>
    </ShaplaColumns>
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="12">
        <div class="yousaidit-designer-revenue__filter flex items-center">
          <ShaplaRadio v-for="_type in report_types" :key="_type.key" theme="primary"
                       :rounded="false" v-model="state.report_type" :value="_type.key"
                       @update:modelValue="changeReportTypeChange"
          >{{ _type.label }}
          </ShaplaRadio>
          <div class="yousaidit-designer-revenue__filter-custom" v-if="state.report_type==='custom'">
            <ShaplaInput label="From" type="date" v-model="state.date_from"/>
            <ShaplaInput label="To" type="date" v-model="state.date_to"/>
            <ShaplaButton theme="primary" :disabled="!(state.date_from && state.date_to)" @click="handleCustomFilter">
              Apply
            </ShaplaButton>
          </div>
        </div>
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaTable :show-cb="false" :items="store.commissions" :columns="columns"/>
        <div class="mt-4">
          <ShaplaTablePagination :total-items="total_items" :per-page="state.per_page"
                                 :current-page="revenue_current_page" @paginate="paginate"/>
        </div>
      </ShaplaColumn>
    </ShaplaColumns>
  </div>
</template>

<script lang="ts" setup>
import {
  ShaplaButton,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaInput,
  ShaplaRadio,
  ShaplaTable,
  ShaplaTablePagination
} from '@shapla/vue-components';
import ReportCard from "../components/ReportCard.vue";
import useDesignerDashboardStore from "../store";
import {computed, onMounted, reactive} from "vue";

const store = useDesignerDashboardStore();
const columns = [
  {key: 'product_title', label: 'Title'},
  {key: 'card_size', label: 'Card Size'},
  {key: 'marketplace', label: 'Marketplace'},
  {key: 'created_at', label: 'Sale Date'},
  {key: 'order_quantity', label: 'Qty', numeric: true},
  {key: 'total_commission', label: 'Total Commission', numeric: true},
]
const report_types = [
  {key: 'today', label: 'Today'},
  {key: 'yesterday', label: 'Yesterday'},
  {key: 'current_week', label: 'Last 7 days'},
  {key: 'current_month', label: 'This Month'},
  {key: 'last_month', label: 'Last Month'},
  {key: 'custom', label: 'Custom'},
]

const state = reactive({
  items: [],
  report_type: 'today',
  date_from: '',
  date_to: '',
  per_page: 20,
})

const total_commission = computed(() => store.total_commission)
const unpaid_commission = computed(() => store.unpaid_commission)
const commissions = computed(() => store.commissions)
const paid_commission = computed(() => store.paid_commission)
const total_orders = computed(() => store.total_orders)
const revenue_current_page = computed(() => store.revenue_current_page)
const total_items = computed(() => store.total_commissions_items)
const marketplaces = computed(() => window.DesignerProfile.marketPlaces)

const getCommissions = () => {
  store.getCommission({type: state.report_type, from: state.date_from, to: state.date_to});
}

onMounted(() => {
  let user = window.DesignerProfile.user;
  if (!store.designer_id) {
    store.designer_id = user.id;
  }
  if (!store.total_commission) {
    store.getDesigner();
  }
  if (!store.commissions.length) {
    getCommissions();
  }
})

const paginate = (page: number) => {
  store.revenue_current_page = page;
  getCommissions();
}
const handleCustomFilter = () => {
  if (state.report_type === 'custom') {
    getCommissions();
  }
}
const changeReportTypeChange = (reportType: string) => {
  window.console.log(reportType);
  if (reportType !== 'custom') {
    getCommissions();
  }
}
</script>
