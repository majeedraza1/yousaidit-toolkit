<template>
	<div class="yousaiditcard_admin_order_dispatcher--dispatcher">
		<h1 class="wp-heading-inline">Order Dispatcher</h1>
		<hr class="wp-header-end">

		<div class="button-container">
			<columns>
				<column :tablet="4">
					<shapla-button theme="primary" size="large" fullwidth @click="goToRoute('PrintCards')">
						Print Cards
					</shapla-button>
				</column>
				<column :tablet="4">
					<shapla-button theme="primary" size="large" fullwidth @click="goToRoute('DispatchOrders')">
						Dispatch Orders
					</shapla-button>
				</column>
				<column :tablet="4">
					<shapla-button theme="primary" size="large" fullwidth @click="goToRoute('CompleteOrders')">
						Complete Orders
					</shapla-button>
				</column>
			</columns>
		</div>
	</div>
</template>

<script>
import {columns, column, shaplaButton} from 'shapla-vue-components';

export default {
	name: "Dispatcher",
	components: {columns, column, shaplaButton},
	methods: {
		goToRoute(name) {
			this.$router.push({name: name});
		}
	}
}
</script>

<style lang="scss">
.yousaiditcard_admin_order_dispatcher--dispatcher {
	.button-container {
		max-width: 700px;
		margin: 0 auto;
	}
}
</style>
