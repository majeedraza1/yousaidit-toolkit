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
import axios from "@/utils/axios";
import {textField, shaplaButton, shaplaCheckbox} from 'shapla-vue-components';
import {Spinner} from "@shapla/vanilla-components";

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
			Spinner.show();
			axios.post('web-login', {
				username: this.user_login,
				password: this.password,
				remember: this.remember,
			}).then(() => {
				// Spinner.hide();
				window.location.reload();
			}).catch(error => {
				Spinner.hide();
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
