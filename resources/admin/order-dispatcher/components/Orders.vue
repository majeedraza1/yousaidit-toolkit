<template>
  <div>
    <columns :multiline="true">
      <column :tablet="12">
        <div class="flex">
          <div class="flex-1"></div>
          <div class="space-x-2">
            <slot name="actions"></slot>
            <shapla-button @click="$store.dispatch('refreshFromShipStation')" :shadow="true"
                           theme="default" size="small"> Refresh Orders
            </shapla-button>
            <shapla-button @click="$store.dispatch('mergePackingSlip')" :shadow="true" theme="primary"
                           :disabled="!(checked_items.length > 1)" size="small">Packing Slip
            </shapla-button>
          </div>
        </div>
      </column>
      <column :tablet="2">
        <select-field
            label="Card size"
            v-model="card_size"
            :options="card_sizes"
            :clearable="false"
            @change="filterOrderData"
        />
      </column>
      <column :tablet="4">
        <select-field
            label="Inner Message"
            v-model="inner_message"
            :options="filterOptions"
            :clearable="false"
            @change="filterOrderData"
        />
      </column>
      <column :tablet="6">
        <pagination :total_items="order_pagination.totalCount" :per_page="order_pagination.limit"
                    :current_page="order_pagination.currentPage" @pagination="paginate"/>
      </column>
      <column :tablet="12">
        <data-table :columns="columns" :items="orders" :actions="actions" index="orderId" action-column="art_work"
                    :selectedItems="checked_items" @item:select="checkedItems" @action:click="onActionClick">
          <template v-slot:orderId="data">
            <a target="_blank"
               :href="`/wp-admin/admin-ajax.php?action=yousaidit_ship_station_order&order_id=${data.row.orderId}`"
            ><strong>{{ data.row.orderId }}</strong></a>
            <br>
            <span>{{ data.row.storeName }}</span>
          </template>
          <template v-slot:invoice="data">
            <div class="flex flex-col space-y-1">
              <shapla-button size="small" :shadow="true" :href="invoiceUrl(data.row.orderId)"
                             @click.prevent="showInvoice(data.row.orderId)">Packing Slip
              </shapla-button>
              <shapla-button size="small" :shadow="true" @click.prevent="$emit('dispatch',data.row.orderId)">Dispatch
              </shapla-button>
            </div>
          </template>
          <template v-slot:customer="data">
            <strong>{{ data.row.customer_full_name }}</strong><br>
            <span v-if="data.row.customer_email">
            <strong>Email:</strong> {{ data.row.customer_email }}<br>
          </span>
            <span v-if="data.row.customer_phone">
            <strong>Phone:</strong>
            {{ data.row.customer_phone }}
            <br>
          </span>
            <span v-html="formatAddress(data.row.shipping_address)"> </span>
          </template>
          <template v-slot:art_work="data">
            <art-work-items :item="data.row"/>
          </template>
        </data-table>
      </column>
      <column :tablet="12">
        <pagination :total_items="order_pagination.totalCount" :per_page="order_pagination.limit"
                    :current_page="order_pagination.currentPage" @pagination="paginate"/>
      </column>
    </columns>
    <modal :active="showPdfUploadModal" title="Upload PDF" @close="showPdfUploadModal = false">
      <template v-for="_product in activeOrder.products">
        <div v-if="_product.product_sku" :key="_product.shipstation_item_id"
             class="flex items-center w-full border border-solid border-gray-200 p-2 rounded mb-2">
          <div class="w-2/4">{{ _product.title }}</div>
          <div class="w-1/4 flex items-center space-x-1">
            <svg v-if="_product.pdf_id" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
              <path fill="none" d="M0 0h24v24H0z"/>
              <path
                  d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z"/>
            </svg>
            <span>{{ _product.product_sku }}</span>
          </div>
          <div class="w-1/4">
            <shapla-button v-if="!_product.pdf_id" theme="primary" size="small" outline @click="addPdf(_product)">Add
              PDF
            </shapla-button>
          </div>
        </div>
      </template>
    </modal>
  </div>
</template>

<script>
import {mapState} from 'vuex';
import {
  column,
  columns,
  dataTable,
  iconContainer,
  modal,
  pagination,
  selectField,
  shaplaButton
} from 'shapla-vue-components'
import ArtWorkItems from "../components/ArtWorkItems";
import wpMediaUploader from "@/admin/utils/WpMediaUploader";
import axios from "@/utils/axios";

export default {
  name: "Orders",
  components: {ArtWorkItems, shaplaButton, dataTable, pagination, columns, column, selectField, modal, iconContainer},
  data() {
    return {
      columns: [
        {key: 'art_work', label: 'Art Work'},
        {key: 'customer', label: 'Customer Details'},
        {key: 'orderId', label: 'ShipStation Order ID'},
        {key: 'door_delivery', label: 'Door Delivery'},
        {key: 'invoice', label: 'Invoice'},
      ],
      actions: [
        {key: 'dispatch', label: 'Dispatch'},
        {key: 'packing_slip', label: 'Packing Slip'},
        {key: 'upload_pdf', label: 'Upload PDF'},
      ],
      filterOptions: [
        {value: 'any', label: 'Any'},
        {value: 'no', label: 'Card without inner message'},
        {value: 'yes', label: 'Card with inner message'},
      ],
      inner_message: 'any',
      card_size: 'any',
      card_sizes: [
        {value: 'any', label: 'Any'},
        {value: 'square', label: 'Square'},
        {value: 'a4', label: 'A4'},
        {value: 'a5', label: 'A5'},
        {value: 'a6', label: 'A6'},
      ],
      showPdfUploadModal: false,
      activeOrder: {},
      activeOrderItem: {},
    }
  },
  computed: {
    ...mapState(['loading', 'orders', 'order_pagination', 'checked_items'])
  },
  mounted() {
    this.$store.commit('SET_LOADING_STATUS', false);
    if (!this.orders.length) {
      this.getOrders();
    }
  },
  methods: {
    formatAddress(address) {
      return address.replace(/<br>\u21b5/g, ', ');
    },
    refreshFromShipStation() {
      this.$store.dispatch('refreshFromShipStation');
    },
    getOrders() {
      this.$store.dispatch('getOrders');
    },
    paginate(page) {
      this.$store.dispatch('paginate', page);
    },
    checkedItems(items) {
      this.$store.commit('SET_CHECKED_ITEMS', items);
    },
    filterOrderData(value) {
      if (this.card_sizes.map(_size => _size.value).indexOf(value) !== -1) {
        this.$store.commit('SET_CARD_SIZE', value);
      } else {
        this.$store.commit('SET_INNER_MESSAGE', value);
      }
      this.$store.commit('SET_CHECKED_ITEMS', []);
      this.getOrders();
    },
    showInvoice(orderId) {
      window.open(this.invoiceUrl(orderId), '_blank');
    },
    invoiceUrl(orderId) {
      return ajaxurl + '?action=stackonet_order_packing_slip&id=' + orderId;
    },
    addPdf(product) {
      this.activeOrderItem = {
        product_id: product.id,
        product_sku: product.product_sku,
        order_id: this.activeOrder.orderId,
        order_item_id: product.shipstation_item_id,
        store_id: this.activeOrder.storeId,
        card_size: product.card_size,
        pdf_id: 0,
        pdf_width: 0,
        pdf_height: 0,
      }
      wpMediaUploader().then(id => {
        this.activeOrderItem.pdf_id = id;
        axios.post(Stackonet.root + '/order-item-pdf', this.activeOrderItem).then(response => {
          window.console.log(response);
        })
      })
    },
    onActionClick(action, item) {
      if ('packing_slip' === action) {
        this.showInvoice(item);
      }
      if ('dispatch' === action) {
        this.$emit('dispatch', item.orderId)
      }
      if ('upload_pdf' === action) {
        this.activeOrder = item;
        this.showPdfUploadModal = true;
      }
    }
  }
}
</script>

<style lang="scss">
.stackonet-orders-list-table {
  .row-actions {
    display: none !important;
  }

  .select--pdf-size {
    height: 2.8em;
  }

  .yousaidit-loop-product {
    align-items: center;
    display: flex;
    justify-content: flex-start;

    svg {
      width: 1.5em;
      height: 1.5em;
      fill: currentColor;
    }
  }
}

.yousaidit-loop-product {
  a {
    max-width: 250px;
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.flex {
  display: flex;
}

.spacer {
  flex-grow: 1;
}
</style>
