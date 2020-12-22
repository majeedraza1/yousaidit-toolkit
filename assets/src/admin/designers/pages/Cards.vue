<template>
	<div>
		<h1 class="wp-heading-inline">Cards</h1>
		<hr class="wp-header-end">
		<columns multiline>
			<column :tablet="4">
				<select-field
						label="Filter by designer"
						:options="designers"
						label-key="display_name"
						value-key="id"
						v-model="designer"
				/>
			</column>
			<column :tablet="4"></column>
			<column :tablet="4">
				<search-form placeholder="Search card ..." @input="handleSearchInput" @search="handleSearch"/>
			</column>
			<column :tablet="8">
				<status-list :statuses="statuses" @change="handleStatusChange"/>
			</column>
			<column :tablet="4">
				<pagination :current_page="current_page" :per_page="per_page" :total_items="total_items"
							@pagination="paginate"/>
			</column>
			<column :tablet="12">
				<data-table
						:items="items"
						:columns="columns"
						:show-cb="false"
						:actions="actions"
						@action:click="handleActionClick"
				>
					<template slot="card_sizes" slot-scope="data">
						<template v-for="(_size,index) in card_sizes"
								  v-if="data.row.card_sizes.indexOf(_size.value) !== -1">
							<template v-if="index === 0">{{_size.label}}</template>
							<template v-else>, {{_size.label}}</template>
						</template>
					</template>
					<template slot="status" slot-scope="data">
						<span v-for="_status in statuses" v-if="_status.key === data.row.status">
							{{_status.label}}
						</span>
					</template>
					<template slot="designer" slot-scope="data">
						<a href="" @click.prevent="goToDesignerProfile(data.row.designer)">
							{{data.row.designer.display_name}}
						</a>
					</template>
				</data-table>
			</column>
			<column :tablet="12">
				<pagination :current_page="current_page" :per_page="per_page" :total_items="total_items"
							@pagination="paginate"/>
			</column>
		</columns>
	</div>
</template>

<script>
	import dataTable from 'shapla-data-table';
	import pagination from 'shapla-data-table-pagination';
	import searchForm from 'shapla-search-form';
	import selectField from 'shapla-select-field';
	import {column, columns} from 'shapla-columns';
	import statusList from 'shapla-data-table-status';
	import axios from "axios";

	export default {
		name: "Cards",
		components: {dataTable, pagination, columns, column, searchForm, statusList, selectField},
		data() {
			return {
				items: [],
				columns: [
					{key: 'card_title', label: 'Title'},
					{key: 'designer', label: 'Designer'},
					{key: 'card_sizes', label: 'Sizes'},
					{key: 'status', label: 'Status'},
					{key: 'card_sku', label: 'SKU'},
					{key: 'total_sale', label: 'Total Sales', numeric: true},
				],
				current_page: 1,
				per_page: 20,
				total_items: 0,
				search: '',
				status: 'processing',
				statuses: [],
				designers: [],
				designer: '',
				actions: [
					{key: 'view', label: 'View'}
				],
			}
		},
		mounted() {
			this.$store.commit('SET_LOADING_STATUS', false);
			this.getItems();
			this.getDesigners();
		},
		watch: {
			designer(newValue) {
				this.getItems();
			}
		},
		computed: {
			card_sizes() {
				return DesignerProfile.card_sizes.map(size => {
					return {
						value: size.slug,
						label: size.name
					}
				});
			},
		},
		methods: {
			handleStatusChange(status) {
				this.status = status.key;
				this.getItems();
			},
			handleSearchInput(search) {
				if (search.length < 1) {
					this.handleSearch('');
				}
			},
			handleSearch(search) {
				this.search = search;
				this.getItems();
			},
			paginate(page) {
				this.current_page = page;
				this.getItems();
			},
			getItemsFilteredByDesigner() {
			},
			getItems() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.get(Stackonet.root + '/designers-cards', {
					params: {
						page: this.current_page,
						per_page: this.per_page,
						search: this.search,
						status: this.status,
						designer_id: this.designer,
					}
				}).then(response => {
					let data = response.data.data;
					this.items = data.items;
					this.statuses = data.statuses;
					this.total_items = data.pagination.total_items;
					this.$store.commit('SET_LOADING_STATUS', false);
				}).catch(errors => {
					console.log(errors);
					this.$store.commit('SET_LOADING_STATUS', false);
				})
			},
			handleActionClick(action, item) {
				if ('view' === action) {
					this.$router.push({name: 'Card', params: {id: item.id}});
				}
			},
			goToDesignerProfile(designer) {
				this.$router.push({name: 'Designer', params: {id: designer.id}});
			},
			getDesigners() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.get(Stackonet.root + '/designers', {params: {page: 1, per_page: 100,}}).then(response => {
					let data = response.data.data;
					this.designers = data.items;
					this.$store.commit('SET_LOADING_STATUS', false);
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
