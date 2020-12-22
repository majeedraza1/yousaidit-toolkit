<template>
	<div>
		<h1 class="wp-heading-inline">Complete Orders</h1>
		<hr class="wp-header-end">
		<orders>
			<shapla-button @click="$store.dispatch('refreshFromShipStation')" :shadow="true" theme="primary">
				Refresh Orders
			</shapla-button>
			<shapla-button :shadow="true" theme="secondary" @click="showModal = true">
				Scan Order
			</shapla-button>
		</orders>
		<modal :active="showModal" v-if="showModal" @close="showModal = false" type="box" content-size="large">
			<div class="shapla-modal-box-content">
				<barcode-search-form v-model="search" @submit="scanBarcode"/>
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
	import shaplaButton from 'shapla-button';
	import modal from 'shapla-modal';
	import OrderInfo from "../components/OrderInfo";
	import BarcodeSearchForm from "../components/BarcodeSearchForm";
	import {mapState} from "vuex";

	export default {
		name: "CompleteOrders",
		components: {BarcodeSearchForm, OrderInfo, Orders, shaplaButton, modal},
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
		},
		watch: {
			showModal() {
				this.search = '';
				this.errorText = '';
				this.activeOrder = {};
			}
		},
		mounted() {
			this.$store.commit('SET_CURRENT_PAGE', 1);
			this.$store.commit('SET_ORDER_STATUS', 'shipped');
			this.$store.dispatch('getOrders', true);
		},
		methods: {
			scanBarcode(search) {
				this.errorText = '';
				this.activeOrder = {};
				let orderId = parseInt(search);
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
			}
		}
	}
</script>

<style scoped>

</style>
