<template>
	<div class="stackonet_services_dashboard has-login-or-reg-form">
		<div>
			<tabs alignment="center">
				<tab name="Login" selected>
					<login/>
				</tab>
				<tab name="Registration">
					<registration/>
				</tab>
			</tabs>
		</div>
		<spinner :active="loading">Loading</spinner>
		<notification :options="notification"/>
	</div>
</template>

<script>
	import {mapState} from 'vuex';
	import {tabs, tab} from 'shapla-tabs';
	import spinner from 'shapla-spinner';
	import notification from 'shapla-notifications';
	import Login from "./Login";
	import Registration from "./Registration";

	export default {
		name: "LoginOrRegistration",
		components: {tabs, tab, spinner, notification, Registration, Login},
		computed: {
			...mapState(['loading', 'notification']),
		}
	}
</script>

<style lang="scss">
	.stackonet_services_dashboard.has-login-or-reg-form {
		display: flex;
		justify-content: center;
		align-items: center;
		position: fixed;
		top: 0;
		left: 0;
		background: #fff;
		width: 100%;
		height: 100%;
		z-index: 99;
	}
</style>
