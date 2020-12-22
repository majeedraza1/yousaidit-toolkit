<template>
	<div class="yousaidit-admin-paypal-payouts">
		<h1 class="wp-heading-inline">PayPal Payout</h1>
		<hr class="wp-header-end">
		<div style="margin-bottom: 1rem;display: flex; justify-content: flex-end;">
			<shapla-button theme="primary" @click="syncPayment">Sync</shapla-button>
		</div>
		<div style="background: #fff;padding: 16px;">
			<columns multiline>
				<column :tablet="3">ID</column>
				<column :tablet="9">{{payment.payment_id}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Payment Batch ID</column>
				<column :tablet="9">{{payment.payment_batch_id}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Payment Status</column>
				<column :tablet="9">{{payment.payment_status}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Currency</column>
				<column :tablet="9">{{payment.currency}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Total Amount</column>
				<column :tablet="9">{{payment.amount}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Created</column>
				<column :tablet="9">{{payment.created_at}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Synced</column>
				<column :tablet="9">{{payment.updated_at}}</column>
			</columns>
		</div>

		<h2 class="title">Payout Items</h2>
		<toggles>
			<toggle :name="`Designer PayPal Email: ${item.paypal_email}`"
					:subtext="`Status: ${item.transaction_status}`"
					v-for="item in items" :key="item.item_id">
				<columns multiline>
					<column :tablet="3">Item ID</column>
					<column :tablet="9">{{item.item_id}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Designer ID</column>
					<column :tablet="9">{{item.designer_id}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Currency</column>
					<column :tablet="9">{{item.currency}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Total Commission Amount</column>
					<column :tablet="9">{{item.total_commissions}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Orders IDs</column>
					<column :tablet="9">{{item.order_ids.toString()}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Commissions IDs</column>
					<column :tablet="9">{{item.commission_ids.toString()}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Note to PayPal</column>
					<column :tablet="9">{{item.note}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Designer PayPal Email</column>
					<column :tablet="9">{{item.paypal_email}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">PayPal Payout Item ID</column>
					<column :tablet="9">{{item.payout_item_id}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">PayPal transaction status</column>
					<column :tablet="9">{{item.transaction_status}}</column>
				</columns>
				<columns multiline>
					<column :tablet="3">Note from PayPal</column>
					<column :tablet="9">{{item.error_message}}</column>
				</columns>
			</toggle>
		</toggles>
	</div>
</template>

<script>
	import axios from "axios";
	import {columns, column} from 'shapla-columns';
	import {toggles, toggle} from 'shapla-toggles';
	import shaplaButton from 'shapla-button'

	export default {
		name: "PayPalPayout",
		components: {columns, column, shaplaButton, toggles, toggle},
		data() {
			return {
				id: 0,
				payment: {},
				items: [],
			}
		},
		mounted() {
			this.id = this.$route.params.id;
			this.getItem();
		},
		methods: {
			getItem() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.get(window.DesignerProfile.restRoot + '/paypal-payouts/' + this.id).then(response => {
					this.$store.commit('SET_LOADING_STATUS', false);
					let data = response.data.data;
					this.payment = data.payment;
					this.items = data.items;
				}).catch(errors => {
					console.log(errors);
					this.$store.commit('SET_LOADING_STATUS', false);
				});
			},
			syncPayment() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.post(window.DesignerProfile.restRoot + '/paypal-payouts/' + this.payment.payment_id + '/sync').then(() => {
					this.$store.commit('SET_LOADING_STATUS', false);
					this.getItem();
				}).catch(errors => {
					console.log(errors);
					this.$store.commit('SET_LOADING_STATUS', false);
				});
			}
		}
	}
</script>

<style scoped>

</style>
