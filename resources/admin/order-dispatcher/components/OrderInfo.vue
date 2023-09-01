<template>
  <div>
    <div class="order-information">
      <div class="section section--header">
        <div class="flex">
          <div>
            <div>&nbsp;</div>
            <div><strong>Order Date:</strong> {{ formatDate(order.order_date) }}</div>
          </div>
          <div class="spacer"></div>
          <div>
            <div><strong>Order Status:</strong> {{ getStatusText(order.orderStatus) }}</div>
            <div><strong>Order Number:</strong> {{ order.orderId }}</div>
            <div><strong>Store:</strong> {{ order.storeName }}({{ order.storeId }})</div>
            <div v-if="order.print_shipping_label_url">
              <strong>{{ order.storeName }} Shipping Label: </strong>
              <a :href="order.print_shipping_label_url" target="_blank">Print</a>
            </div>
          </div>
        </div>
      </div>
      <div class="section section--products">
        <data-table :columns="columns" :items="order.products" :mobile-width="300" :show-cb="false">
          <template slot="title" slot-scope="data">
            <div class="order-information__product flex">
              <image-container container-width="96px" container-height="96px">
                <img :src="data.row.product_thumbnail" alt=""/>
              </image-container>
              <div>
                <div class="product-title">{{ data.row.title }}</div>
                <div class="product-product_sku">
                  <span>SKU: </span>{{ data.row.product_sku }}
                </div>
                <div class="product-product_options" v-if="data.row.options">
                  <div class="product-product_option" :key="index"
                       v-for="(_option,index) in data.row.options">
                    <span>{{ _option.name }}: </span>{{ _option.value }}
                  </div>
                </div>
              </div>
            </div>
          </template>
        </data-table>
      </div>
      <div class="section section--customer_notes" v-if="order.customer_notes">
        <div class="customer_notes flex">
          <span>Customer Note: </span>
          <span>{{ order.customer_notes }}</span>
        </div>
      </div>
      <div class="section section--customer_notes" v-if="order.internal_notes">
        <div class="customer_notes flex">
          <span>Internal Note: </span>
          <span>{{ order.internal_notes }}</span>
        </div>
      </div>
      <div class="section section--address">
        <div class="shipping-address flex">
          <span>Address: </span>
          <span>{{ address }}</span>
        </div>
      </div>
      <div class="section section--footer">
        <div class="actions flex">
          <shapla-button theme="secondary" @click="$store.dispatch('printAddress',order.orderId)">
            Print Address
          </shapla-button>
          <div class="spacer"></div>
          <div class="door_delivery">
            <div><strong>Door Delivery:</strong></div>
            {{ order.door_delivery }}
          </div>
          <div class="spacer"></div>
          <div class="shipping_service">
            <div>Shipping Method:</div>
            <div>{{ order.shipping_service }}</div>
          </div>
          <div class="spacer"></div>
          <shapla-button theme="primary" @click="showDispatchModal = true">Dispatch</shapla-button>
        </div>
      </div>
    </div>
    <modal :active="showDispatchModal" @close="showDispatchModal = false" type="box" content-size="small">
      <mark-as-shipped :order-id="order.orderId" @shipped="shipped"/>
    </modal>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import {
  column,
  columns,
  dataTable,
  iconContainer,
  imageContainer,
  modal,
  selectField,
  shaplaButton,
  shaplaCheckbox,
  textField
} from 'shapla-vue-components';
import MarkAsShipped from "./MarkAsShipped";

export default {
  name: "OrderInfo",
  components: {
    MarkAsShipped, iconContainer,
    shaplaButton, imageContainer, dataTable, modal, selectField, columns, column, textField, shaplaCheckbox
  },
  props: {
    order: {type: Object}
  },
  data() {
    return {
      showDispatchModal: false,
      columns: [
        {key: 'title', label: 'Product'},
        {key: 'quantity', label: 'Qty', numeric: true},
      ]
    }
  },
  computed: {
    ...mapState(['carriers']),
    address() {
      if (!Object.keys(this.order).length) {
        return '';
      }

      return this.order.shipping_address.replace(/<br\/>/g, ", ");
    }
  },
  methods: {
    formatDate(dateString) {
      let date = new Date(dateString), month = date.getMonth();
      let months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

      return `${date.getDate()} ${months[month]}, ${date.getFullYear()}`;
    },
    getStatusText(key) {
      let statuses = {
        awaiting_payment: 'Awaiting Payment',
        awaiting_shipment: 'Awaiting Shipment',
        shipped: 'Shipped',
        on_hold: 'On Hold',
        cancelled: 'Cancelled',
      }

      return statuses[key];
    },
    shipped(data) {
      this.$store.dispatch('dispatch', data).then(() => {
        this.$emit('shipped');
      });
      this.showDispatchModal = false;
    }
  }
}
</script>

<style lang="scss">
.order-information {
  .section {
    &:not(:first-child) {
      margin-top: 15px;
    }
  }

  &__product {
    margin-top: 18px;
    margin-bottom: 18px;
  }

  .shapla-image-container {
    background-color: #f1f1f1;
    display: inline-block;

    + div {
      margin-left: 18px;
    }
  }
}

.door_delivery {
  text-align: center;
}
</style>
