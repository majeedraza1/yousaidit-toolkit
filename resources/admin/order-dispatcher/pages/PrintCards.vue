<template>
	<div>
		<h1 class="wp-heading-inline">Print Cards</h1>
		<hr class="wp-header-end mb-4">
		<div>
			<div class="mb-4 flex">
				<div class="flex-1"></div>
				<shapla-button theme="primary" size="small" @click="getItems(true)">Refresh</shapla-button>
			</div>
			<tabs>
				<tab name="PDF Merger" selected>
					<pdf-size-info
						:items="store_items"
						@need-force-refresh="getItems(true)"
					/>
				</tab>
				<tab name="Trade Orders">
					<pdf-size-info
						:items="marketplace_items"
						@need-force-refresh="getItems(true)"
					/>
				</tab>
				<tab name="Other Products">
					<pdf-size-info
						:items="others_items"
						@need-force-refresh="getItems(true)"
					/>
				</tab>
			</tabs>
		</div>
	</div>
</template>

<script>
import {shaplaButton, tab, tabs} from 'shapla-vue-components';
import {mapState} from 'vuex';
import PdfSizeInfo from "../components/PdfSizeInfo";
import axios from "axios";

export default {
	name: "PrintCards",
	components: {PdfSizeInfo, shaplaButton, tabs, tab},
	computed: {
		...mapState(['checked_items']),
	},
	data() {
		return {
			store_items: [],
			marketplace_items: [],
			others_items: [],
		}
	},
	methods: {
		getItems(force = false) {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/orders/card-sizes', {params: {force: force}}).then(response => {
				let data = response.data.data;
				if (force) {
					this.store_items = [];
					this.marketplace_items = [];
					this.others_items = [];
				}
				this.calculateItems(data.items);
			}).catch(error => {
				console.log(error);
			}).finally(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		calculateItems(items) {
			items.forEach(item => {
				if (item.is_other_products) {
					this.others_items.push(item);
				} else if (item.is_trade_order) {
					this.marketplace_items.push(item);
				} else {
					this.store_items.push(item);
				}
			})
		}
	},
	mounted() {
		this.getItems();
	}
}
</script>
