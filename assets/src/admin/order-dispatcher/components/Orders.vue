<template>
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
			<data-table
				:columns="columns"
				:items="orders"
				index="orderId"
				action-column="art_work"
				:selectedItems="checked_items"
				@item:select="checkedItems"
			>
				<template slot="orderId" slot-scope="data">
					<a target="_blank"
					   :href="`/wp-admin/admin-ajax.php?action=yousaidit_ship_station_order&order_id=${data.row.orderId}`"
					><strong>{{ data.row.orderId }}</strong></a>
				</template>
				<template slot="invoice" slot-scope="data">
					<shapla-button :shadow="true" @click="showInvoice(data.row.orderId)">Packing Slip
					</shapla-button>
				</template>
				<template slot="customer" slot-scope="data">
					<strong>{{ data.row.customer_full_name }}</strong><br>
					<span
						v-if="data.row.customer_email"><strong>Email:</strong> {{ data.row.customer_email }}<br></span>
					<span v-if="data.row.customer_phone">
					<strong>Phone:</strong>
					{{ data.row.customer_phone }}
					<br>
				</span>
					<span v-html="data.row.shipping_address"> </span>
				</template>
				<template slot="art_work" slot-scope="data">
					<div v-for="_product in data.row.products" v-if="_product.product_sku"
						 class="yousaidit-loop-product">
						<a :href="_product.edit_product_url" :title="_product.title">{{ _product.title }}</a>
						<span class="yousaidit-loop-product__sku">({{ _product.product_sku }})</span>
						<a class="yousaidit-loop-product__art_work" target="_blank" v-if="_product.art_work.url"
						   :href="_product.art_work.url" title="Download Card PDF">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
								<path fill="none" d="M0 0h24v24H0z"/>
								<path
									d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z"/>
							</svg>
						</a>
						<span class="yousaidit-loop-product__inner_message" v-if="_product.has_inner_message"
							  title="Inner Message">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
									<path
										d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
									<path d="M0 0h24v24H0z" fill="none"/>
								</svg>
							</span>
					</div>
				</template>
			</data-table>
		</column>
		<column :tablet="12">
			<pagination :total_items="order_pagination.totalCount" :per_page="order_pagination.limit"
						:current_page="order_pagination.currentPage" @pagination="paginate"/>
		</column>
	</columns>
</template>

<script>
import {mapState} from 'vuex';
import {column, columns, dataTable, pagination, selectField, shaplaButton} from 'shapla-vue-components'

export default {
	name: "Orders",
	components: {shaplaButton, dataTable, pagination, columns, column, selectField},
	data() {
		return {
			columns: [
				{key: 'art_work', label: 'Art Work'},
				{key: 'customer', label: 'Customer Details'},
				{key: 'orderId', label: 'ShipStation Order ID'},
				{key: 'door_delivery', label: 'Door Delivery'},
				{key: 'invoice', label: 'Invoice'},
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
		}
	},
	computed: {
		...mapState(['loading', 'orders', 'order_pagination', 'checked_items']),
		actions() {
			return [
				{key: 'view', label: 'View'},
			]
		}
	},
	mounted() {
		this.$store.commit('SET_LOADING_STATUS', false);
		if (!this.orders.length) {
			this.getOrders();
		}
	},
	methods: {
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
			let url = ajaxurl + '?action=stackonet_order_packing_slip&id=' + orderId;
			window.open(url, '_blank');
		},
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
