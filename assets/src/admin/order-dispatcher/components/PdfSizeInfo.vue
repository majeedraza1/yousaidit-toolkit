<template>
	<div class="w-full">
		<div class="mb-4 flex">
			<div class="flex-1"></div>
			<shapla-button theme="primary" size="small" @click="forceRefresh">Refresh</shapla-button>
		</div>
		<div class="md:flex flex-wrap -m-4" v-if="Object.keys(items).length">
			<div class="p-4 md:w-6/12 lg:w-4/12 xl:w-3/12" v-for="(item, key) in items" :key="key">
				<div class="shadow p-4 bg-white h-full flex flex-col">
					<div>
						<strong>{{ item.width }}</strong>x<strong>{{ item.height }}</strong>
						<small>pdf size</small>
					</div>
					<div>
						<strong>{{ item.card_size }}</strong> card
						<template v-if="item.card_type === 'dynamic'">
							- <span class="text-primary">Dynamic</span>
						</template>
					</div>
					<div>Total <strong>{{ item.items.length }}</strong> Item(s)</div>
					<div>{{ item.inner_message ? 'Contain Inner Message' : '&nbsp;' }}</div>
					<div class="flex-grow"></div>
					<div v-if="item.card_type === 'dynamic' && dynamic_card.generating"
					     class="text-xs border border-primary border-solid p-1">
						Generating: {{ dynamic_card.items_to_generate }}<br>
						<div v-if="dynamic_card.success_items">Success: {{ dynamic_card.success_items }}</div>
						<div v-if="dynamic_card.error_items">Error: {{ dynamic_card.error_items }}</div>
					</div>
					<div class="mt-4 flex space-y-2 flex-wrap">
						<shapla-button v-if="item.card_type === 'dynamic' && item.to_generate.length"
						               :class="{'is-loading':dynamic_card.generating}" size="small" fullwidth
						               @click="handleDynamicCardGeneration(item)">
							Generate Dynamic Card
						</shapla-button>
						<shapla-button v-if="item.inner_message" theme="default" size="small" fullwidth target="_blank"
						               :href="get_pdf_url(item,'im')">Merge Inner Message
						</shapla-button>
						<shapla-button theme="secondary" outline size="small" fullwidth target="_blank"
						               :href="get_pdf_url(item,'pdf')">Merge PDF
						</shapla-button>
						<shapla-button v-if="item.inner_message" theme="primary" size="small" fullwidth target="_blank"
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

const dynamicCardDefault = {
	generating: false,
	items_to_generate: 0,
	remaining_items: 0,
	success_items: 0,
	error_items: 0,
}
export default {
	name: "PdfSizeInfo",
	components: {shaplaButton},
	data() {
		return {
			items: {},
			dynamic_card: JSON.parse(JSON.stringify(dynamicCardDefault))
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
			params.set('card_type', item.card_type);
			params.set('ids', _items.toString());
			return _url.toString();
		},
		mergePdf(item) {
			window.open(this.get_pdf_url(item), '_blank');
		},
		handleDynamicCardGeneration(item) {
			this.$dialog.confirm(
				'Generating all dynamic card is a CPU resource consuming task.',
				{title: 'Are you Sure?'}
			)
				.then((confirmed) => {
					if (confirmed) {
						this.dynamic_card.generating = true;
						this.dynamic_card.items_to_generate = item.to_generate.length;
						this.dynamic_card.remaining_items = item.to_generate.length;
						item.to_generate.forEach(_item => {
							this.generate_dynamic_pdf(_item.wc_order_id, _item.wc_order_item_id).then(data => {
								this.dynamic_card.success_items += 1;
							}).catch(error => {
								this.dynamic_card.error_items += 1;
							}).finally(() => {
								this.dynamic_card.remaining_items -= 1;

								if (this.dynamic_card.remaining_items < 1) {
									this.dynamic_card.generating = false;
									this.dynamic_card = JSON.parse(JSON.stringify(dynamicCardDefault));
									this.forceRefresh();
								}
							});
						})
					}
				})
		},
		generate_dynamic_pdf(wc_order_id, wc_order_item_id) {
			let _url = new URL(Stackonet.ajaxurl),
				params = _url.searchParams;
			params.set('action', 'generate_dynamic_card_pdf');
			params.set('order_id', wc_order_id);
			params.set('order_item_id', wc_order_item_id);
			return new Promise((resolve, reject) => {
				axios.get(_url.toString()).then(response => {
					let data = response.data.data;
					resolve(data);
				}).catch(error => {
					resolve(error.response.data);
				})
			})
		}
	},
	mounted() {
		this.getItems();
	}
}
</script>

<style lang="scss">
</style>
