<template>
	<div class="has-login-form">
		<form @submit.prevent="submitForm" class="stackonet-support-ticket-login-form">
			<div class="form-control">
				<text-field
						label="Email or Username"
						autocomplete="username"
						v-model="user_login"
						:has-error="hasUserLoginError"
						:validation-text="errors.user_login?errors.user_login[0]:''"
				/>
			</div>
			<div class="form-control">
				<text-field
						type="password"
						label="Password"
						v-model="password"
						autocomplete="current-password"
						:has-error="hasPasswordError"
						:validation-text="errors.password?errors.password[0]:''"
				/>
			</div>
			<div class="form-control form-control--remember">
				<div>
					<shapla-checkbox v-model="remember">Remember me</shapla-checkbox>
				</div>
				<div><a :href="lostPasswordUrl">Forgot your password?</a></div>
			</div>
			<div>
				<shapla-button theme="primary" :fullwidth="true" :disabled="!canSubmit">Log in</shapla-button>
			</div>
		</form>
	</div>
</template>

<script>
	import axios from 'axios'
	import textField from 'shapla-text-field';
	import shaplaButton from 'shapla-button';
	import shaplaCheckbox from "shapla-checkbox";

	export default {
		name: "Login",
		components: {shaplaButton, textField, shaplaCheckbox},
		data() {
			return {
				user_login: '',
				password: '',
				remember: false,
				errors: {
					user_login: [],
					password: [],
				},
			}
		},
		computed: {
			lostPasswordUrl() {
				return DesignerProfile.lostPasswordUrl;
			},
			canSubmit() {
				return !!(this.user_login.length >= 4 && this.password.length >= 4);
			},
			hasUserLoginError() {
				return !!(this.errors.user_login && this.errors.user_login.length);
			},
			hasPasswordError() {
				return !!(this.errors.password && this.errors.password.length);
			}
		},
		methods: {
			submitForm() {
				this.loading = true;
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.post(window.DesignerProfile.restRoot + '/web-login', {
					username: this.user_login,
					password: this.password,
					remember: this.remember,
				}).then(() => {
					// this.$store.commit('SET_LOADING_STATUS', false);
					window.location.reload();
				}).catch(error => {
					this.$store.commit('SET_LOADING_STATUS', false);
					if (error.response && error.response.data.errors) {
						this.errors = error.response.data.errors;
					}
				})
			}
		}
	}
</script>

<style lang="scss">
	.stackonet-support-ticket-login-form {
		max-width: 320px;
		min-width: 300px;

		.form-control {
			margin-bottom: 1rem;

			&--remember {
				display: flex;
				justify-content: space-between;
			}
		}
	}
</style>
