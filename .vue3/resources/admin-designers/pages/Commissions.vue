<template>
  <div class="yousaidit-admin-commissions">
    <h1 class="wp-heading-inline">Commissions</h1>
    <hr class="wp-header-end">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="12" class="flex">
        <div class="flex-grow"></div>
        <ShaplaButton theme="primary" size="small" @click="state.show_sync_commission_modal = true">Sync Commission
        </ShaplaButton>
      </ShaplaColumn>
    </ShaplaColumns>
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="12">
        <div class="mb-4 flex items-center">
          <a href="#" @click.prevent="state.show_filter_sidenav = true">Show Filter</a>
          <div class="flex-1"></div>
          <ShaplaTablePagination
              :total-items="state.total_items"
              :per-page="state.per_page"
              :current-page="state.page"
              @paginate="paginate"
          />
        </div>
        <ShaplaTable
            :show-cb="false"
            :items="state.commissions"
            :columns="columns"
        >
          <template v-slot:product_title="data">
            <a href="#" @click.prevent="goToCardPage(data.row.card_id)">{{ data.row.product_title }}</a>
          </template>
          <template v-slot:designer_name="data">
            <a href="#" @click.prevent="goToDesignerPage(data.row.designer_id)">{{ data.row.designer_name }}</a>
          </template>
          <template v-slot:payment_status="data">
					<span :class="`payment-status--${data.row.payment_status}`">
						{{ data.row.payment_status }}
					</span>
          </template>
          <template v-slot:marketplace="data">
						<span v-for="_market in state.marketplaces">
              <template v-if="_market.key === data.row.marketplace">
						{{ _market.label }}
              </template>
						</span>
          </template>
          <template v-slot:order_id="data">
            <a v-if="data.row.created_via !== 'shipstation-api'"
               :href="`/wp-admin/post.php?post=${data.row.order_id}&action=edit`" target="_blank">
              #{{ data.row.order_id }}
            </a>
            <span v-else>#{{ data.row.order_id }}</span>
          </template>
          <template v-slot:action="data">
            <ShaplaIcon hoverable @click="deleteCommission(data.row)">
              <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"
                   style="fill:var(--shapla-error, #f14668)">
                <path
                    d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5l-1-1h-5l-1 1H5v2h14V4z"/>
              </svg>
            </ShaplaIcon>
          </template>
        </ShaplaTable>
        <div class="mt-4">
          <ShaplaTablePagination
              :total-items="state.total_items"
              :per-page="state.per_page"
              :current-page="state.page"
              @paginate="paginate"
          />
        </div>
      </ShaplaColumn>
    </ShaplaColumns>
    <ShaplaSidenav :active="state.show_filter_sidenav" nav-width="300px" position="right" :show-overlay="true"
                   @close="state.show_filter_sidenav = false">
      <div class="yousaidit-designer-revenue__filter">
        <h3 class="sidenav-section-title">Filter by date</h3>
        <ShaplaRadio v-for="_type in report_types" :key="_type.key" theme="primary"
                     :rounded="false" v-model="state.report_type" :value="_type.key"
                     @change="changeReportTypeChange"
        >{{ _type.label }}
        </ShaplaRadio>
        <div class="yousaidit-designer-revenue__filter-custom" v-if="state.report_type==='custom'">
          <ShaplaInput label="From" type="date" v-model="state.date_from"/>
          <ShaplaInput label="To" type="date" v-model="state.date_to"/>
          <ShaplaButton theme="primary" :disabled="!(state.date_from && state.date_to)" @click="handleCustomFilter">
            Apply
          </ShaplaButton>
        </div>

        <h3 class="sidenav-section-title">Filter by designer</h3>
        <ShaplaSelect
            label="Filter by designer"
            :options="state.designers"
            label-key="display_name"
            value-key="id"
            v-model="state.designer"
        />

        <h3 class="sidenav-section-title">Filter by payment status</h3>
        <ShaplaRadio v-for="_status in payment_statuses" :key="_status.key" theme="primary"
                     :rounded="false" v-model="state.payment_status" :value="_status.key"
                     @change="filterByPaymentStatus"
        >{{ _status.label }}
        </ShaplaRadio>

        <h3 class="sidenav-section-title">Filter by order status</h3>
        <ShaplaRadio v-for="(_status,key) in order_statuses" :key="`order-status-${key}`" theme="primary"
                     :rounded="false"
                     v-model="state.order_status" :value="key" @change="filterByPaymentStatus">{{ _status }}
        </ShaplaRadio>
      </div>
    </ShaplaSidenav>
  </div>
  <ShaplaModal :active="state.show_sync_commission_modal" title="Sync Commission from ShipStation"
               @close="state.show_sync_commission_modal = false">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="12">
        <ShaplaInput
            label="Start Date"
            type="date"
            v-model="state.sync_commission_start_data"
            help-text="Required. Orders date greater than the specified date."
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaInput
            label="End Date"
            type="date"
            v-model="state.sync_commission_end_data"
            help-text="Orders less than or equal to the specified date."
        />
      </ShaplaColumn>
      <ShaplaColumn :tablet="12">
        <ShaplaSelect
            label="ShipStation Order Status"
            v-model="state.sync_commission_order_status"
            help-text="Filter by order status."
            :options="shipstation_order_statuses"
            :clearable="false"
        />
        <div class="min-h-[200px]"></div>
      </ShaplaColumn>
    </ShaplaColumns>
    <template v-slot:foot>
      <ShaplaButton theme="primary" @click="syncCommission" :disabled="!canSyncCommission">Sync Now</ShaplaButton>
    </template>
  </ShaplaModal>
</template>

<script lang="ts" setup>
import axios from "../../utils/axios";
import {
  ShaplaButton,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaIcon,
  ShaplaInput,
  ShaplaModal,
  ShaplaRadio,
  ShaplaSelect,
  ShaplaSidenav,
  ShaplaTable,
  ShaplaTablePagination
} from '@shapla/vue-components';
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import {computed, onMounted, reactive, watch} from "vue";
import {useRouter} from "vue-router";

const router = useRouter();

const state = reactive({
  commissions: [],
  marketplaces: [],
  order_status: 'all',
  payment_status: 'all',
  report_type: 'current_month',
  date_from: '',
  date_to: '',
  show_filter_sidenav: false,
  designer: '',
  designers: [],
  page: 1,
  total_items: 0,
  per_page: 50,
  show_sync_commission_modal: false,
  sync_commission_start_data: '',
  sync_commission_end_data: '',
  sync_commission_order_status: '',
});
const columns = [
  {key: 'order_id', label: 'Order'},
  {key: 'product_title', label: 'Title'},
  {key: 'designer_name', label: 'Designer'},
  {key: 'card_size', label: 'Card Size'},
  {key: 'marketplace', label: 'Marketplace'},
  {key: 'created_at', label: 'Sale Date'},
  {key: 'payment_status', label: 'Payment Status'},
  {key: 'order_quantity', label: 'Qty', numeric: true},
  {key: 'total_commission', label: 'Total Commission', numeric: true},
  {key: 'action', label: 'Action', numeric: true},
]
const report_types = [
  {key: 'today', label: 'Today'},
  {key: 'yesterday', label: 'Yesterday'},
  {key: 'current_week', label: 'Last 7 days'},
  {key: 'current_month', label: 'This Month'},
  {key: 'last_month', label: 'Last Month'},
  {key: 'custom', label: 'Custom'},
]
const payment_statuses = [
  {key: 'all', label: 'All'},
  {key: 'unpaid', label: 'Unpaid'},
  {key: 'paid', label: 'Paid'},
]

const shipstation_order_statuses = [
  {value: '', label: 'Any Status'},
  {value: 'awaiting_payment', label: 'awaiting_payment'},
  {value: 'awaiting_shipment', label: 'awaiting_shipment'},
  {value: 'pending_fulfillment', label: 'pending_fulfillment'},
  {value: 'shipped', label: 'shipped'},
  {value: 'on_hold', label: 'on_hold'},
  {value: 'cancelled', label: 'cancelled'},
  {value: 'rejected_fulfillment', label: 'rejected_fulfillment'},
];

const order_statuses = computed(() => Object.assign({all: 'All'}, window.DesignerProfile.order_statuses));

const handleCustomFilter = () => {
  if (state.report_type === 'custom') {
    getCommissions();
  }
}
const changeReportTypeChange = (reportType: string) => {
  if (reportType !== 'custom') {
    getCommissions();
  }
}
const filterByPaymentStatus = () => {
  getCommissions();
}
const paginate = (page) => {
  state.page = page;
  getCommissions();
}
const getCommissions = () => {
  Spinner.show();
  let params = {
    report_type: state.report_type,
    date_from: state.date_from,
    date_to: state.date_to,
    payment_status: state.payment_status,
    order_status: state.order_status,
    page: state.page,
    per_page: state.per_page,
  };
  if (state.designer) {
    params['designer_id'] = state.designer;
  }
  axios.get('designers-commissions', {params: params})
      .then(response => {
        let data = response.data.data;
        state.commissions = data.commissions;
        state.marketplaces = data.marketplaces;
        state.total_items = data.pagination.total_items;
      })
      .catch(errors => {
        console.log(errors);
      })
      .finally(() => {
        state.show_filter_sidenav = false;
        Spinner.hide();
      });
}
const getDesigners = () => {
  Spinner.show();
  axios.get('designers', {params: {page: 1, per_page: 100,}}).then(response => {
    let data = response.data.data;
    state.designers = data.items;
    Spinner.hide();
  }).catch(errors => {
    console.log(errors);
    Spinner.hide();
  });
}
const deleteCommission = (data) => {
  Dialog.confirm('Are you sure to delete commission?').then(confirmed => {
    if (confirmed) {
      Spinner.show();
      axios.delete('designers-commissions/' + data.commission_id).then(() => {
        Notify.success('Commission deleted successfully')
        getCommissions();
      }).catch(errors => {
        console.log(errors);
      }).finally(() => {
        Spinner.hide();
      });
    }
  })
}

const canSyncCommission = computed<boolean>(() => !!state.sync_commission_start_data)

const syncCommission = () => {
  Spinner.show();
  axios
      .get('designers-commissions/sync', {
        params: {
          order_date_start: state.sync_commission_start_data,
          order_date_end: state.sync_commission_end_data,
          order_status: state.sync_commission_order_status,
        }
      })
      .then(() => {
        Notify.success('A background task is running to sync commissions from ShipStation API.')
        state.show_sync_commission_modal = false;
        state.sync_commission_start_data = '';
        state.sync_commission_end_data = '';
      })
      .finally(() => {
        Spinner.hide();
      });
}

const goToDesignerPage = (designer_id: number | string) => {
  router.push({name: 'Designer', params: {id: designer_id}});
}

const goToCardPage = (card_id: number | string) => {
  router.push({name: 'Card', params: {id: card_id}});
}

watch(() => state.designer, () => getCommissions())

onMounted(() => {
  getCommissions();
  getDesigners();
})
</script>
