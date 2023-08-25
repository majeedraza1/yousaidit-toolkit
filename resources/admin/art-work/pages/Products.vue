<template>
	<div class="wrap">
		<h1 class="wp-heading-inline">Products</h1>
		<hr class="wp-header-end">
		<columns :multiline="true">
			<column :tablet="8">&nbsp;</column>
			<column :tablet="4">
				<search-form @search="searchProduct"/>
			</column>
			<column :tablet="12">
				<data-table
					:columns="columns"
					:items="items"
					:actions="actions"
					@action:click="handleActionClick"
					:show-cb="false"
				>
					<template slot="product_sku" slot-scope="data">
						<span v-html="getProductSku(data.row)"/>
					</template>
					<template slot="art_work" slot-scope="data">
						<template v-if="data.row.product_type === 'variable'">
							<div v-for="variation in data.row.variations">
								<shapla-chip v-if="variation.art_work">{{ variation.art_work.title }}</shapla-chip>
								<span v-else> - </span>
							</div>
						</template>
						<template v-else>
							<shapla-chip v-if="data.row.art_work.title">{{ data.row.art_work.title }}</shapla-chip>
							<span v-else> - </span>
						</template>
					</template>
				</data-table>
			</column>
			<column :tablet="12">
				<pagination
					:total_items="total_items"
					:per_page="50"
					:current_page="current_page"
					@pagination="paginate"
				/>
			</column>
		</columns>
		<modal :active="showPdfModal" @close="hidePdfModal" title="PDF Cards">
			<table class="shapla-data-table shapla-data-table--fullwidth" v-if="Object.keys(active_item).length">
				<thead>
				<tr class="shapla-data-table__header-row">
					<th class="shapla-data-table__cell--non-numeric">SKU</th>
					<th class="shapla-data-table__cell--non-numeric">PDF Card</th>
				</tr>
				</thead>
				<tbody>
				<template v-if="active_item.product_type === 'variable'">
					<tr v-for="variation in active_item.variations" :key="variation.id">
						<td class="shapla-data-table__cell--non-numeric">{{ variation.sku }}</td>
						<td class="shapla-data-table__cell--non-numeric">
							<pdf-uploader v-model="variation.art_work" :id="variation.id"/>
						</td>
					</tr>
				</template>
				<template v-else>
					<tr>
						<td class="shapla-data-table__cell--non-numeric">{{ active_item.product_sku }}</td>
						<td class="shapla-data-table__cell--non-numeric">
							<pdf-uploader v-model="active_item.art_work" :id="active_item.id"/>
						</td>
					</tr>
				</template>
				</tbody>
			</table>
			<template v-slot:foot>
				<shapla-button theme="default" shadow @click="hidePdfModal">Close</shapla-button>
			</template>
		</modal>
	</div>
</template>

<script>
import axios from 'axios'
import PdfUploader from "./PdfUploader";
import {
	column,
	columns,
	dataTable,
	modal,
	pagination,
	searchForm,
	shaplaButton,
	shaplaChip
} from 'shapla-vue-components';

export default {
	name: "Products",
	components: {PdfUploader, dataTable, pagination, columns, column, searchForm, modal, shaplaButton, shaplaChip},
	data() {
		return {
			columns: [
				{key: 'title', label: 'Product Title'},
				{key: 'product_type', label: 'Product Type'},
				{key: 'product_sku', label: 'Product SKU'},
				{key: 'art_work', label: 'Art Work'},
			],
			actions: [
				{key: 'pdf_cards', label: 'PDF Cards'},
			],
			items: [],
			total_items: 0,
			current_page: 1,
			active_item: {},
			showPdfModal: false,
		}
	},
	mounted() {
		this.$store.commit('SET_LOADING_STATUS', false);
		this.getProducts();
	},
	methods: {
		getProductSku(item) {
			if ('variable' === item.product_type) {
				let html = '';
				item.variations.forEach(variation => {
					html += variation.sku + '<br/>';
				});
				return html;
			}
			return item.product_sku;
		},
		handleActionClick(action, item) {
			if ('pdf_cards' === action) {
				this.active_item = item;
				this.showPdfModal = true;
			}
		},
		hidePdfModal() {
			this.active_item = {};
			this.showPdfModal = false;
		},
		paginate(page) {
			this.current_page = page;
			this.getProducts();
		},
		getProducts() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/products?page=' + this.current_page).then(response => {
				this.items = response.data.data.items;
				this.total_items = response.data.data.total_items;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(error => {
				console.log(error);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		searchProduct(query) {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/products?search=' + query).then(response => {
				this.items = response.data.data.items;
				this.total_items = response.data.data.total_items;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(error => {
				console.log(error);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		}
	}
}
</script>

<style scoped>

</style>
