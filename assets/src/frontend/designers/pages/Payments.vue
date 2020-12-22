<template>
	<div class="yousaidit-designer-payment">
		<h2 class="yousaidit-designer-dashboard__section-title">Payment Details</h2>
		<data-table
				:show-cb="false"
				:items="items"
				:columns="columns"
		>
			<template slot="commission_ids" slot-scope="data">
				{{data.row.commission_ids.toString()}}
			</template>
		</data-table>
	</div>
</template>

<script>
	import dataTable from 'shapla-data-table';
	import axios from "axios";

	export default {
		name: "Payments",
		components: {dataTable},
		data() {
			return {
				items: [],
				columns: [
					{key: 'paypal_email', label: 'Email'},
					{key: 'created_at', label: 'Payment Date'},
					{key: 'transaction_status', label: 'Payment Status'},
					{key: 'commission_ids', label: 'Revenue ID(s)'},
					{key: 'total_commissions', label: 'Total Earning', numeric: true},
				],
			}
		},
		mounted() {
			this.getItems();
		},
		methods: {
			getItems() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.get(window.DesignerProfile.restRoot + '/designer-payments').then(response => {
					this.$store.commit('SET_LOADING_STATUS', false);
					let data = response.data.data;
					this.items = data.items;
				}).catch(errors => {
					this.$store.commit('SET_LOADING_STATUS', false);
					console.log(errors);
				});
			},
		}
	}
</script>

<style scoped>

</style>
