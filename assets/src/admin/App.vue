<template>
	<div class="repair-art-work-container">
		<router-view/>
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
.repair-art-work-container {
	position: relative;
	box-sizing: border-box;

	* {
		box-sizing: border-box;
	}
}
</style>
