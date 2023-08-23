<template>
  <div>
    <h1 class="wp-heading-inline">Dispatch Orders</h1>
    <hr class="wp-header-end">
    <orders @dispatch="onDispatch">
      <template v-slot:actions>
        <shapla-button :shadow="true" size="small" theme="secondary" @click="showModal = true">
          Scan Order
        </shapla-button>
      </template>
    </orders>
    <modal :active="showModal" v-if="showModal" @close="showModal = false" type="box" content-size="large">
      <div class="shapla-modal-box-content">
        <form action="#" @submit.prevent="scanBarcode" autocomplete="off">
          <div class="field--input-container">
            <label class="screen-reader-text" for="input--search">Scan code or enter ShipStation ID</label>
            <input id="input--search" class="input--search" type="text" v-model="search"
                   placeholder="Scan code or enter ShipStation ID">
            <shapla-button theme="primary">Search</shapla-button>
            <shapla-button theme="default" @click="search = ''" class="button--clear"
                           :class="{'is-active':search.length}">Clear
            </shapla-button>
          </div>
        </form>
        <p class="search-results-error" v-if="errorText" v-html="errorText"></p>
        <template v-if="hasActiveOrder">
          <order-info :order="activeOrder" @shipped="showModal = false"/>
        </template>
      </div>
    </modal>
  </div>
</template>

<script>
import Orders from "../components/Orders";
import {modal, shaplaButton} from 'shapla-vue-components';
import {mapState} from 'vuex';
import OrderInfo from "../components/OrderInfo";

export default {
  name: "DispatchOrders",
  components: {OrderInfo, Orders, shaplaButton, modal},
  data() {
    return {
      showModal: false,
      activeOrder: {},
      activeOrderFromServer: {},
      search: '',
      errorText: '',
    }
  },
  computed: {
    ...mapState(['orders']),
    hasActiveOrder() {
      return !!Object.keys(this.activeOrder).length
    },
    address() {
      if (!this.hasActiveOrder) {
        return '';
      }

      return this.activeOrder.shipping_address.replace(/<br\/>/g, ", ");
    }
  },
  watch: {
    showModal(value) {
      this.search = '';
      this.errorText = '';
      this.activeOrder = {};
      if (value) {
        setTimeout(() => document.querySelector('.input--search').focus(), 10);
      }
    }
  },
  methods: {
    scanBarcode() {
      this.errorText = '';
      this.activeOrder = {};
      let orderId = parseInt(this.search);
      if (Number.isNaN(orderId)) {
        this.errorText = 'Invalid number';
        return;
      }
      let order = this.orders.find(order => order.orderId === orderId);
      if (!(typeof order === 'object' && Object.keys(order))) {
        this.errorText = 'No order found.';
        this.getOrder(orderId);
        return;
      }
      this.activeOrder = order;
    },
    getOrder(orderId) {
      this.$store.dispatch('getOrder', orderId).then(data => {
        this.activeOrderFromServer = data;
        this.activeOrder = data;
        this.errorText = '';
      });
    },
    onDispatch(orderId) {
      this.showModal = true;
      this.search = orderId.toString();
      this.getOrder(orderId);
    }
  },
  mounted() {
    this.$store.commit('SET_CURRENT_PAGE', 1);
    this.$store.commit('SET_ORDER_STATUS', 'awaiting_shipment');
    this.$store.dispatch('getOrders', true);
  }
}
</script>

<style lang="scss">
.shapla-modal-box-content {
  background-color: #fff;
  padding: 1rem;
  border-radius: 4px;
  min-height: 300px;
}

.field--input-container {
  display: flex;
  justify-content: center;
  border-bottom: 1px dashed rgba(#000, .12);
  padding-bottom: 1rem;
  margin-bottom: 1rem;

  .input--search {
    height: 2.8em;
    margin-right: .5em;
    min-width: 230px;
  }
}

.search-results-error {
  text-align: center;
  font-size: 16px;
  color: var(--shapla-error, red);
}

.shipping-address,
.customer_notes {
  background: #f1f1f1;
  padding: .5rem;
  margin: .5rem 0;
  flex-direction: column;

  span:first-child {
    color: var(--shapla-primary);
    font-weight: bold;
  }
}

.actions {
  margin-top: 2rem;
}

.shipping_service {
  text-align: center;

  div:first-child {
    color: var(--shapla-primary);
    font-weight: bold;
  }
}

.button--clear {
  margin-left: 5px;
  visibility: hidden;

  &.is-active {
    visibility: visible;
  }
}
</style>
