<template>
  <div class="yousaidit-admin-paypal-payouts">
    <h1 class="wp-heading-inline">PayPal Payout</h1>
    <hr class="wp-header-end">

    <tabs>
      <tab selected name="Status">
        <columns multiline>
          <column :tablet="4" v-for="count_card in count_cards" :key="count_card.key">
            <div>
              <report-card :title="count_card.label">
                {{ count_card.count }}
              </report-card>
            </div>
          </column>
        </columns>
      </tab>
      <tab name="Payment History">
        <data-table
            :show-cb="false"
            :items="items"
            :columns="columns"
            :actions="actions"
            @action:click="handleActionClick"
        />
      </tab>
    </tabs>
    <div style="position: fixed;bottom:15px;right:15px;z-index: 100">
      <shapla-button fab theme="primary" size="large" @click="showModal = true">+</shapla-button>
    </div>
    <modal :active="showModal" type="box" title="New Payout" content-class="shapla-modal-confirm"
           :show-close-icon="false" :close-on-background-click="false" content-size="small">
      <div class="shapla-modal-confirm__content">
        <div class="shapla-modal-confirm__icon is-info">
          <div class="shapla-modal-confirm__icon-content">!</div>
        </div>
        <h3 class="shapla-modal-confirm__title">Are you sure to create a new payout?</h3>
        <div class="shapla-modal-confirm__message">
          Only designers, whom unpaid commissions (for completed orders) are more than {{ min_amount }}, will
          be paid.

          <p>Choose order status to pay.</p>
          <div class="text-left mt-4">
            <template v-for="info in count_cards">
              <shapla-checkbox v-if="info.status" :value="info.key" v-model="statuses_to_pay">
                {{ info.status }}
              </shapla-checkbox>
            </template>
          </div>
        </div>
      </div>
      <div class="shapla-modal-confirm__actions">
        <button class="shapla-button" @click.prevent="showModal = false">Cancel</button>
        <button class="shapla-button is-primary" @click.prevent="payNow" :disabled="statuses_to_pay.length < 1">
          Ok
        </button>
      </div>
    </modal>
  </div>
</template>

<script>
import axios from "@/utils/axios";
import {column, columns, dataTable, modal, shaplaButton, shaplaCheckbox, tab, tabs} from 'shapla-vue-components';
import ReportCard from "../../../components/ReportCard";
import {Notify, Spinner} from "@shapla/vanilla-components";

export default {
  name: "PayPalPayouts",
  components: {dataTable, columns, column, shaplaButton, tabs, tab, ReportCard, modal, shaplaCheckbox},
  data() {
    return {
      showModal: false,
      min_amount: 1,
      count_cards: [],
      items: [],
      columns: [
        {key: 'payment_batch_id', label: 'Batch ID'},
        {key: 'payment_status', label: 'Payment Status'},
        {key: 'currency', label: 'Currency'},
        {key: 'amount', label: 'Amount'},
        {key: 'created_at', label: 'Created'},
        {key: 'updated_at', label: 'Updated'},
      ],
      actions: [
        {key: 'view', label: 'View'},
        {key: 'sync', label: 'Sync'},
      ],
      statuses_to_pay: [],
    }
  },
  mounted() {
    Spinner.hide();
    this.getItems();
  },
  methods: {
    getItems() {
      Spinner.show();
      let params = {};
      axios.get('paypal-payouts', {
        params: params
      }).then(response => {
        this.items = response.data.data.items;
        this.count_cards = response.data.data.count_cards;
        this.min_amount = response.data.data.min_amount;
        Spinner.hide();
      }).catch(errors => {
        console.log(errors);
        Spinner.hide();
      });
    },
    handleActionClick(action, item) {
      if ('sync' === action) {
        this.syncFromPayPal(item);
      }
      if ('view' === action) {
        this.$router.push({name: 'Payout', params: {id: item.payment_id}});
      }
    },
    syncFromPayPal(item) {
      Spinner.show();
      axios.post('paypal-payouts/' + item.payment_id + '/sync').then(() => {
        Spinner.hide();
        this.getItems();
      }).catch(errors => {
        console.log(errors);
        Spinner.hide();
      });
    },
    payNow() {
      Spinner.show();
      axios.post('paypal-payouts', {order_status: this.statuses_to_pay}).then(() => {
        Spinner.hide();
        Notify.success('Payout has been run successfully.')
        this.getItems();
      }).catch(errors => {
        Spinner.hide();
        Notify.error(errors.response.data.message)
      });
    }
  }
}
</script>
