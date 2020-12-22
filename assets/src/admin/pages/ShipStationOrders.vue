<template>
	<div class="wrap stackonet-orders-list-table">
		<h1 class="wp-heading-inline">ShipStation Orders</h1>
		<hr class="wp-header-end">
		<orders/>
	</div>
</template>

<script>
import axios from 'axios'
import {mapState} from 'vuex';
import {column, columns, dataTable, pagination, selectField, shaplaButton} from 'shapla-vue-components'
import Orders from "../order-dispatcher/components/Orders";

export default {
	name: "ShipStationOrders",
	components: {Orders, shaplaButton, dataTable, pagination, columns, column, selectField},
	data() {
		return {
			columns: [
				{key: 'art_work', label: 'Art Work'},
				{key: 'customer', label: 'Customer Details'},
				{key: 'title', label: 'ShipStation Order ID'},
				{key: 'invoice', label: 'Invoice'},
			],
			items: [],
			total_items: 0,
			pagination: {
				currentPage: 1,
				totalCount: 0,
				limit: 100,
			},
			current_page: 1,
			filterOptions: [
				{value: 'no', label: 'Card without inner message'},
				{value: 'yes', label: 'Card with inner message'},
			],
			checked_items: [],
			inner_message: 'no',
			card_size: 'square',
			card_sizes: [
				{value: 'square', label: 'Square'},
				{value: 'a4', label: 'A4'},
				{value: 'a5', label: 'A5'},
				{value: 'a6', label: 'A6'},
			],
		}
	},
	computed: {
		...mapState(['loading']),
		actions() {
			return [
				{key: 'view', label: 'View'},
			]
		},
		canMergePdf() {
			return !!(this.inner_message && this.card_size && this.checked_items.length > 1);
		},
	},
	mounted() {
		this.$store.commit('SET_LOADING_STATUS', false);
		this.getOrders();
	},
	methods: {
		showInvoice(orderId) {
			let url = ajaxurl + '?action=stackonet_order_packing_slip&id=' + orderId;
			// window.location.href = url;
			window.open(url, '_blank');
		},
		refreshFromShipStation() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/orders?page=' + this.current_page + '&force=1').then(response => {
				this.items = response.data.data.items;
				this.total_items = response.data.data.total_items;
				this.pagination = response.data.data.pagination;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(error => {
				console.log(error);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		getOrders() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/orders', {
				params: {page: this.current_page, card_size: this.card_size, inner_message: this.inner_message}
			}).then(response => {
				this.items = response.data.data.items;
				this.total_items = response.data.data.total_items;
				this.pagination = response.data.data.pagination;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(error => {
				console.log(error);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		paginate(page) {
			this.current_page = page;
			this.getOrders();
		},
		checkedItems(items) {
			this.checked_items = items;
		},
		mergePackingSlip() {
			let url = ajaxurl + '?action=stackonet_order_packing_slips&ids=' + this.checked_items.toString();
			window.open(url, '_blank');
		},
		filterOrderData() {
			this.checked_items = [];
			this.getOrders();
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
</style>
