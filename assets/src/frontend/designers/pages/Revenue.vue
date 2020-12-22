<template>
	<div class="yousaidit-designer-revenue">
		<columns multiline>
			<column :tablet="3">
				<report-card title="Unpaid Commission" :content="unpaid_commission" background-color="#f4d4e4"/>
			</column>
			<column :tablet="3">
				<report-card title="Paid Commission" :content="paid_commission" background-color="#dde4ff"/>
			</column>
			<column :tablet="3">
				<report-card title="Total Commission" :content="total_commission" background-color="#fdfad3"/>
			</column>
			<column :tablet="3">
				<report-card title="Total Orders" :content="total_orders" background-color="#d0ffe0"/>
			</column>
		</columns>
		<tabs alignment="center" size="medium" @tab:change="handleTabChange">
			<tab name="Today's Orders" selected>
				<data-table
						:show-cb="false"
						:items="commissions"
						:columns="columns"
				/>
			</tab>
			<tab name="Previous Orders">
				<columns multiline>
					<column :tablet="12">
						<div class="yousaidit-designer-revenue__filter">
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
						</div>
					</column>
					<column :tablet="12">
						<data-table
								:show-cb="false"
								:items="commissions"
								:columns="columns"
						/>
					</column>
				</columns>
			</tab>
		</tabs>
	</div>
</template>

<script>
	import {columns, column} from 'shapla-columns';
	import {tabs, tab} from 'shapla-tabs';
	import dataTable from 'shapla-data-table';
	import radioButton from 'shapla-radio-button';
	import shaplaButton from 'shapla-button';
	import textField from 'shapla-text-field'
	import ReportCard from "../components/ReportCard";
	import {mapState} from 'vuex';

	export default {
		name: "Revenue",
		components: {ReportCard, columns, column, tabs, tab, dataTable, radioButton, shaplaButton, textField},
		data() {
			return {
				items: [],
				columns: [
					{key: 'product_title', label: 'Title'},
					{key: 'card_size', label: 'Card Size'},
					{key: 'created_at', label: 'Sale Date'},
					{key: 'order_quantity', label: 'Qty', numeric: true},
					{key: 'total_commission', label: 'Total Commission', numeric: true},
				],
				report_types: [
					{key: 'yesterday', label: 'Yesterday'},
					{key: 'current_week', label: 'Last 7 days'},
					{key: 'current_month', label: 'This Month'},
					{key: 'last_month', label: 'Last Month'},
					{key: 'custom', label: 'Custom'},
				],
				report_type: 'today',
				date_from: '',
				date_to: '',
			}
		},
		computed: {
			...mapState(['designer_id', 'total_commission', 'unpaid_commission', 'unique_customers', 'commissions',
				'designer', 'paid_commission', 'total_orders']),
		},
		mounted() {
			let user = DesignerProfile.user;
			if (!this.designer_id) {
				this.$store.commit('SET_DESIGNER_ID', user.id);
			}
			if (!this.total_commission) {
				this.$store.dispatch('getDesigner');
			}
			if (!this.commissions.length) {
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
			handleTabChange(tab) {
				if ("Today's Orders" === tab.name) {
					this.report_type = 'today';
					this.getCommissions();
				}
				if ("Previous Orders" === tab.name) {
					this.report_type = 'current_week';
					this.getCommissions();
				}
			},
			getCommissions() {
				this.$store.dispatch('getCommission', {
					type: this.report_type,
					from: this.date_from,
					to: this.date_to
				});
			}
		}
	}
</script>

<style lang="scss">
	.yousaidit-designer-revenue {
		&__filter {
			> * {
				&:not(:last-child) {
					margin-right: 10px;
				}
			}
		}

		&__filter-custom {
			display: inline-flex;
			align-items: center;

			.shapla-button {
				margin-left: 10px;
			}
		}
	}
</style>
