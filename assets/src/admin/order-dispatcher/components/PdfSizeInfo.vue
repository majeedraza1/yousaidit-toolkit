<template>
	<div class="w-full">
		<div class="mb-4 flex">
			<div class="flex-1"></div>
			<shapla-button theme="primary" size="small" @click="forceRefresh">Refresh</shapla-button>
		</div>
		<div class="md:flex flex-wrap -m-4" v-if="Object.keys(items).length">
			<div class="p-4 md:w-3/12 lg:w-2/12" v-for="(item, key) in items" :key="key">
				<div class="shadow p-4 bg-white">
					<div>
						<strong>{{ item.width }}</strong>x<strong>{{ item.height }}</strong>
						<small>pdf size</small>
					</div>
					<div><strong>{{ item.card_size }}</strong> card</div>
					<div>Total <strong>{{ item.items.length }}</strong> Item(s)</div>
					<div>{{ item.inner_message ? 'Contain Inner Message' : '&nbsp;' }}</div>
					<div class="mt-4 flex space-y-2 flex-wrap">
						<shapla-button theme="default" size="small" fullwidth target="_blank"
									   :href="get_pdf_url(item,'im')">Merge Inner Message
						</shapla-button>
						<shapla-button theme="secondary" outline size="small" fullwidth target="_blank"
									   :href="get_pdf_url(item,'pdf')">Merge PDF
						</shapla-button>
						<shapla-button theme="primary" size="small" fullwidth target="_blank"
									   :href="get_pdf_url(item,'both')"> Merge PDF & Inner Message
						</shapla-button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from "axios";
import {shaplaButton} from 'shapla-vue-components'

export default {
	name: "PdfSizeInfo",
	components: {shaplaButton},
	data() {
		return {
			items: {},
		}
	},
	methods: {
		forceRefresh() {
			this.getItems(true);
		},
		getItems(force = false) {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/orders/card-sizes', {params: {force: force}}).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				let data = response.data.data;
				this.items = data.items;
			}).catch(error => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(error);
			})
		},
		get_pdf_url(item, type = 'both') {
			let _items = item.items.map(el => el.shipStation_order_id);
			let _url = new URL(Stackonet.ajaxurl),
				params = _url.searchParams;
			params.set('action', 'yousaidit_download_pdf');
			params.set('type', type);
			params.set('card_size', item.card_size);
			params.set('card_width', item.width);
			params.set('card_height', item.height);
			params.set('inner_message', item.inner_message);
			params.set('ids', _items.toString());
			return _url.toString();
		},
		mergePdf(item) {
			window.open(this.get_pdf_url(item), '_blank');
		}
	},
	mounted() {
		this.getItems();
	}
}
</script>

<style lang="scss">
</style>
