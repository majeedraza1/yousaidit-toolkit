<template>
	<div class="yousaidit-designer-dashboard">

		<div class="section--designs">
			<h2 class="yousaidit-designer-dashboard__section-title">Designs</h2>
			<columns multiline>
				<column :tablet="6" :desktop="4" :widescreen="3" v-for="_status in cards_statuses" :key="_status.key">
					<report-card :title="_status.label" :content="_status.count" :background-color="_status.color"/>
				</column>
			</columns>
		</div>

		<div class="section--revenue">
			<h2 class="yousaidit-designer-dashboard__section-title">Revenue</h2>
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
		</div>
	</div>
</template>

<script>
import {columns, column} from 'shapla-vue-components';
import ReportCard from "../components/ReportCard";
import {mapState} from 'vuex';

export default {
	name: "Report",
	components: {ReportCard, columns, column},
	computed: {
		...mapState(['designer_id', 'designer', 'cards_statuses', 'total_commission', 'unpaid_commission',
			'paid_commission', 'total_orders']),
	},
	mounted() {
		let user = DesignerProfile.user;
		if (!this.designer_id) {
			this.$store.commit('SET_DESIGNER_ID', user.id);
		}
		if (!Object.keys(this.designer).length) {
			this.$store.dispatch('getDesigner');
		}
	}
}
</script>

<style lang="scss">
.yousaidit-designer-dashboard {
	&__section-title {
		text-transform: uppercase;
		font-weight: normal;
		font-size: 1.5em;
	}

	.section--revenue {
		margin-top: 50px;
	}
}
</style>
