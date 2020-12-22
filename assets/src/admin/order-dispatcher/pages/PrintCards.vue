<template>
	<div>
		<h1 class="wp-heading-inline">Print Cards</h1>
		<hr class="wp-header-end mb-4">
		<div>
			<tabs>
				<tab name="PDF Merger" selected>
					<pdf-size-info/>
				</tab>
				<tab name="Packing Slip">
					<orders/>
				</tab>
			</tabs>
		</div>
	</div>
</template>

<script>
import {shaplaButton, tab, tabs} from 'shapla-vue-components';
import Orders from "../components/Orders";
import {mapState} from 'vuex';
import PdfSizeInfo from "../components/PdfSizeInfo";

export default {
	name: "PrintCards",
	components: {PdfSizeInfo, Orders, shaplaButton, tabs, tab},
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

<style scoped>

</style>
