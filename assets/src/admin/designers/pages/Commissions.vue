<template>
	<div class="yousaidit-admin-commissions">
		<h1 class="wp-heading-inline">Commissions</h1>
		<hr class="wp-header-end">
		<columns multiline>
			<column :tablet="12">
				<a href="#" @click.prevent="show_filter_sidenav = true">Show Filter</a>
			</column>
			<column :tablet="12">
				<data-table
						:show-cb="false"
						:items="commissions"
						:columns="columns"
				>
					<span slot="payment_status" slot-scope="data" :class="`payment-status--${data.row.payment_status}`">
						{{data.row.payment_status}}
					</span>
					<template slot="order_id" slot-scope="data">
						<a :href="`/wp-admin/post.php?post=${data.row.order_id}&action=edit`" target="_blank">
							#{{data.row.order_id}}
						</a>
					</template>
				</data-table>
			</column>
		</columns>
		<side-navigation :active="show_filter_sidenav" nav-width="300px" position="right" :show-overlay="true"
						 @close="show_filter_sidenav = false">
			<div class="yousaidit-designer-revenue__filter">
				<h3 class="sidenav-section-title">Filter by date</h3>
				<radio-button v-for="_type in report_types" :key="_type.key" theme="primary"
							  :rounded="false" v-model="report_type" :value="_type.key"
							  @change="changeReportTypeChange"
				>{{_type.label}}
				</radio-button>
				<div class="yousaidit-designer-revenue__filter-custom" v-if="report_type==='custom'">
					<text-field label="From" type="date" v-model="date_from"/>
					<text-field label="To" type="date" v-model="date_to"/>
					<shapla-button theme="primary" :disabled="!(date_from && date_to)"
								   @click="handleCustomFilter">Apply
					</shapla-button>
				</div>

				<h3 class="sidenav-section-title">Filter by designer</h3>
				<select-field
						label="Filter by designer"
						:options="designers"
						label-key="display_name"
						value-key="id"
						v-model="designer"
				/>

				<h3 class="sidenav-section-title">Filter by payment status</h3>
				<radio-button v-for="_status in payment_statuses" :key="_status.key" theme="primary"
							  :rounded="false" v-model="payment_status" :value="_status.key"
							  @change="filterByPaymentStatus"
				>{{_status.label}}
				</radio-button>

				<h3 class="sidenav-section-title">Filter by order status</h3>
				<radio-button v-for="(_status,key) in order_statuses" :key="`order-status-${key}`" theme="primary" :rounded="false"
							  v-model="order_status" :value="key" @change="filterByPaymentStatus">{{_status}}
				</radio-button>
			</div>
		</side-navigation>
	</div>
</template>

<script>
	import axios from "axios";
	import dataTable from 'shapla-data-table';
	import {columns, column} from 'shapla-columns';
	import radioButton from 'shapla-radio-button';
	import shaplaButton from 'shapla-button';
	import textField from 'shapla-text-field'
	import sideNavigation from 'shapla-side-navigation';
	import selectField from 'shapla-select-field';

	export default {
		name: "Commissions",
		components: {columns, column, dataTable, radioButton, shaplaButton, textField, sideNavigation, selectField},
		data() {
			return {
				commissions: [],
				columns: [
					{key: 'order_id', label: 'Order'},
					{key: 'product_title', label: 'Title'},
					{key: 'designer_name', label: 'Designer'},
					{key: 'card_size', label: 'Card Size'},
					{key: 'created_at', label: 'Sale Date'},
					{key: 'payment_status', label: 'Payment Status'},
					{key: 'order_quantity', label: 'Qty', numeric: true},
					{key: 'total_commission', label: 'Total Commission', numeric: true},
				],
				report_types: [
					{key: 'today', label: 'Today'},
					{key: 'yesterday', label: 'Yesterday'},
					{key: 'current_week', label: 'Last 7 days'},
					{key: 'current_month', label: 'This Month'},
					{key: 'last_month', label: 'Last Month'},
					{key: 'custom', label: 'Custom'},
				],
				payment_statuses: [
					{key: 'all', label: 'All'},
					{key: 'unpaid', label: 'Unpaid'},
					{key: 'paid', label: 'Paid'},
				],
				order_status: 'all',
				payment_status: 'all',
				report_type: 'current_month',
				date_from: '',
				date_to: '',
				show_filter_sidenav: false,
				designer: '',
				designers: [],
			}
		},
		mounted() {
			this.$store.commit('SET_LOADING_STATUS', false);
			this.getCommissions();
			this.getDesigners();
		},
		computed: {
			order_statuses() {
				return Object.assign({all: 'All'}, DesignerProfile.order_statuses);
			}
		},
		watch: {
			designer() {
				this.getCommissions();
			}
		},
		methods: {
			handleCustomFilter() {
				if (this.report_type === 'custom') {
					this.getCommissions();
				}
			},
			changeReportTypeChange(reportType) {
				if (reportType !== 'custom') {
					this.getCommissions();
				}
			},
			filterByPaymentStatus() {
				this.getCommissions();
			},
			getCommissions() {
				this.$store.commit('SET_LOADING_STATUS', true);
				let params = {
					report_type: this.report_type,
					date_from: this.date_from,
					date_to: this.date_to,
					payment_status: this.payment_status,
					order_status: this.order_status,
				};
				if (this.designer) {
					params['designer_id'] = this.designer;
				}
				axios.get(window.DesignerProfile.restRoot + '/designers-commissions', {
					params: params
				}).then(response => {
					this.commissions = response.data.data.commissions;
					this.show_filter_sidenav = false;
					this.$store.commit('SET_LOADING_STATUS', false);
				}).catch(errors => {
					console.log(errors);
					this.show_filter_sidenav = false;
					this.$store.commit('SET_LOADING_STATUS', false);
				});
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

<style lang="scss">
	@import "~shapla-color-system/src/variables";

	.yousaidit-admin-commissions {
		.shapla-sidenav__background,
		.shapla-sidenav__body {
			position: fixed;
			top: 32px;
		}

		.yousaidit-designer-revenue__filter {
			margin-bottom: 30px;
			padding: 1rem;
			display: flex;
			flex-direction: column;

			> *,
			.shapla-text-field {
				margin-bottom: 1rem;
			}
		}

		.sidenav-section-title {
			font-size: 20px;
			border-bottom: 1px solid rgba(#000, 0.12);
			padding-bottom: 10px;
			margin-bottom: 10px;
		}
	}

	.payment-status--unpaid,
	.payment-status--paid {
		border-radius: 3px;
		font-weight: bold;
		padding: 5px;
	}

	.payment-status--paid {
		background: $success;
		color: $on-success;
	}

	.payment-status--unpaid {
		background: $error;
		color: $on-error;
	}
</style>
