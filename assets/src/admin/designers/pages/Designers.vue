<template>
	<div class="yousaidit-admin-designers">
		<h1 class="wp-heading-inline">Designers</h1>
		<hr class="wp-header-end">
		<columns multiline>
			<column :tablet="8"></column>
			<column :tablet="4">
				<search-form placeholder="Search designer ..." @input="handleSearchInput" @search="handleSearch"/>
			</column>
			<column :tablet="12">
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
				/>
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
	import {columns, column} from 'shapla-columns';
	import axios from 'axios';

	export default {
		name: "Designers",
		components: {dataTable, pagination, columns, column, searchForm},
		data() {
			return {
				items: [],
				columns: [
					{key: 'display_name', label: 'Name'},
					{key: 'email', label: 'Email'},
					{key: 'total_cards', label: 'Total Cards', numeric: true},
					{key: 'total_sales', label: 'Total Sales', numeric: true},
				],
				actions: [
					{key: 'view', label: 'View'}
				],
				current_page: 1,
				per_page: 20,
				total_items: 0,
				search: '',
			}
		},
		mounted() {
			this.$store.commit('SET_LOADING_STATUS', false);
			this.getItems();
		},
		methods: {
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
			getItems() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.get(Stackonet.root + '/designers', {
					params: {
						page: this.current_page,
						per_page: this.per_page,
						search: this.search
					}
				}).then(response => {
					let data = response.data.data;
					this.items = data.items;
					this.total_items = data.pagination.total_items;
					this.$store.commit('SET_LOADING_STATUS', false);
				}).catch(errors => {
					console.log(errors);
					this.$store.commit('SET_LOADING_STATUS', false);
				})
			},
			handleActionClick(action, item) {
				if ('view' === action) {
					this.$router.push({name: 'Designer', params: {id: item.id}});
				}
			}
		}
	}
</script>

<style lang="scss">
	.yousaidit-admin-designers {

	}
</style>
