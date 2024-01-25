<template>
	<div class="yousaidit-designer-payment">
		<h2 class="yousaidit-designer-dashboard__section-title">Payment Details</h2>
		<data-table
			:show-cb="false"
			:items="items"
			:columns="columns"
		>
			<template v-slot:commission_ids="data">
				{{ data.row.commission_ids.toString() }}
			</template>
		</data-table>
	</div>
</template>

<script>
import {dataTable} from 'shapla-vue-components';
import axios from "@/utils/axios";

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
			Spinner.show();
			axios.get('designer-payments').then(response => {
				Spinner.hide();
				let data = response.data.data;
				this.items = data.items;
			}).catch(errors => {
				Spinner.hide();
				console.log(errors);
			});
		},
	}
}
</script>
