<template>
	<div class="yousaiditcard_admin_order_dispatcher">
		<div class="wrap">
			<router-view/>
		</div>
		<confirm-dialog/>
		<notification :options="notification"/>
		<spinner :active="loading"/>
	</div>
</template>

<script>
import {mapState} from 'vuex';
import {spinner, notification, ConfirmDialog} from 'shapla-vue-components';

export default {
	name: "App",
	components: {spinner, notification, ConfirmDialog},
	computed: {
		...mapState(['loading', 'notification']),
	}
}
</script>

<style lang="scss">
.yousaiditcard_admin_order_dispatcher {
	position: relative;
	box-sizing: border-box;

	* {
		box-sizing: border-box;
	}
}
</style>
