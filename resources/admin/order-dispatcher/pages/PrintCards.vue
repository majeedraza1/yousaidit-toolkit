<template>
	<div>
		<h1 class="wp-heading-inline">Print Cards</h1>
		<hr class="wp-header-end mb-4">
		<div>
			<tabs>
				<tab name="PDF Merger" selected>
					<pdf-size-info marketplace="main"/>
				</tab>
				<tab name="Trade Orders">
					<pdf-size-info marketplace="trade"/>
				</tab>
				<tab name="Other Products">
					<p>We are working on it.</p>
				</tab>
			</tabs>
		</div>
	</div>
</template>

<script>
import {shaplaButton, tab, tabs} from 'shapla-vue-components';
import {mapState} from 'vuex';
import PdfSizeInfo from "../components/PdfSizeInfo";

export default {
	name: "PrintCards",
	components: {PdfSizeInfo, shaplaButton, tabs, tab},
	computed: {
		...mapState(['checked_items']),
	},
	mounted() {
		this.$store.commit('SET_CURRENT_PAGE', 1);
		this.$store.commit('SET_ORDER_STATUS', 'awaiting_shipment');
		this.$store.dispatch('getOrders', true);
	}
}
</script>
