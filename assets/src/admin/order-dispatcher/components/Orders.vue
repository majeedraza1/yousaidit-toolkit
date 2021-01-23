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
			<data-table :columns="columns" :items="orders" index="orderId" action-column="art_work"
						:selectedItems="checked_items" @item:select="checkedItems">
				<template slot="orderId" slot-scope="data">
					<a target="_blank"
					   :href="`/wp-admin/admin-ajax.php?action=yousaidit_ship_station_order&order_id=${data.row.orderId}`"
					><strong>{{ data.row.orderId }}</strong></a>
				</template>
				<template slot="invoice" slot-scope="data">
					<shapla-button :shadow="true" :href="invoiceUrl(data.row.orderId)"
								   @click.prevent="showInvoice(data.row.orderId)">Packing Slip
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
					<art-work-items :item="data.row"/>
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
import ArtWorkItems from "../components/ArtWorkItems";

export default {
	name: "Orders",
	components: {ArtWorkItems, shaplaButton, dataTable, pagination, columns, column, selectField},
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
			window.open(this.invoiceUrl(orderId), '_blank');
		},
		invoiceUrl(orderId) {
			return ajaxurl + '?action=stackonet_order_packing_slip&id=' + orderId;
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
