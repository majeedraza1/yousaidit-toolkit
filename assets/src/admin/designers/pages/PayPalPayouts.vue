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
			<shapla-button fab theme="primary" size="large" @click="payNow">+</shapla-button>
		</div>
		<modal :active="showModal" title="New Payout" @close="showModal = false">
			Are you sure to create a new payout to pay all unpaid commissions?
		</modal>
	</div>
</template>

<script>
import axios from "axios";
import dataTable from 'shapla-data-table';
import modal from 'shapla-modal';
import {column, columns} from 'shapla-columns';
import {tab, tabs} from 'shapla-tabs';
import shaplaButton from 'shapla-button';
import ReportCard from "../../../components/ReportCard";

export default {
	name: "PayPalPayouts",
	components: {dataTable, columns, column, shaplaButton, tabs, tab, ReportCard, modal},
	data() {
		return {
			showModal: false,
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
		}
	},
	mounted() {
		this.$store.commit('SET_LOADING_STATUS', false);
		this.getItems();
	},
	methods: {
		getItems() {
			this.$store.commit('SET_LOADING_STATUS', true);
			let params = {};
			axios.get(window.DesignerProfile.restRoot + '/paypal-payouts', {
				params: params
			}).then(response => {
				this.items = response.data.data.items;
				this.count_cards = response.data.data.count_cards;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
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
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(window.DesignerProfile.restRoot + '/paypal-payouts/' + item.payment_id + '/sync').then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.getItems();
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			});
		},
		payNow() {
			let config = {
				title: 'Are you sure to create a new payout?',
				message: 'Only designers, whom unpaid commissions are more than 5.00, will be paid.',
				icon: 'info'
			};
			this.$dialog.confirm(config).then(confirmed => {
				if (confirmed) {
					this.$store.commit('SET_LOADING_STATUS', true);
					axios.post(window.DesignerProfile.restRoot + '/paypal-payouts').then(() => {
						this.$store.commit('SET_LOADING_STATUS', false);
						this.$store.commit('SET_NOTIFICATION', {
							type: 'success',
							title: 'Success',
							message: 'Payout has been run successfully.'
						});
						this.getItems();
					}).catch(errors => {
						this.$store.commit('SET_LOADING_STATUS', false);
						this.$store.commit('SET_NOTIFICATION', {
							type: 'error',
							title: 'Error',
							message: errors.response.data.message
						});
					});
				}
			})
		}
	}
}
</script>

<style scoped>

</style>
