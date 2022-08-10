<template>
	<div class="has-registration-form">
		<form @submit.prevent="submitForm" class="stackonet-support-ticket-login-form">
			<div class="form-control">
				<text-field
					label="Name"
					autocomplete="name"
					v-model="name"
					:has-error="hasNameError"
					:validation-text="errors.name?errors.name[0]:''"
				/>
			</div>
			<div class="form-control">
				<text-field
					type="email"
					label="Email"
					autocomplete="email"
					v-model="email"
					:has-error="hasEmailError"
					:validation-text="errors.email?errors.email[0]:''"
				/>
			</div>
			<div class="form-control">
				<text-field
					label="Username"
					autocomplete="username"
					v-model="username"
					:has-error="hasUsernameError"
					:validation-text="errors.username?errors.username[0]:''"
				/>
			</div>
			<div class="form-control form-control--terms">
				<shapla-checkbox v-model="accept_terms"/>
				<span>
					I agree to the
					<a target="_blank" :href="termsUrl">Terms of Service</a>
					and
					<a target="_blank" :href="privacyPolicyUrl">Privacy Policy</a>.
				</span>
			</div>
			<div>
				<shapla-button theme="primary" :fullwidth="true" :disabled="!canSubmit">Log in</shapla-button>
			</div>
		</form>
	</div>
</template>

<script>
import axios from 'axios'
import {mapGetters} from 'vuex';
import {textField, shaplaButton, shaplaCheckbox} from 'shapla-vue-components';

export default {
	name: "Registration",
	components: {shaplaButton, textField, shaplaCheckbox},
	data() {
		return {
			loading: false,
			accept_terms: false,
			username: '',
			email: '',
			name: '',
			errors: {
				username: [],
				email: [],
				name: [],
			},
		}
	},
	computed: {
		...mapGetters(['privacyPolicyUrl', 'termsUrl']),
		isValidEmail() {
			let re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return this.name.length && re.test(this.email);
		},
		canSubmit() {
			return !!(this.username.length >= 4 && this.name.length >= 3 && this.accept_terms && this.isValidEmail);
		},
		hasEmailError() {
			return !!(this.errors.email && this.errors.email.length);
		},
		hasUsernameError() {
			return !!(this.errors.username && this.errors.username.length);
		},
		hasNameError() {
			return !!(this.errors.name && this.errors.name.length);
		}
	},
	methods: {
		submitForm() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(window.DesignerProfile.restRoot + '/registration', {
				name: this.name,
				email: this.email,
				username: this.username,
			}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.errors = {username: [], email: [], name: []};
				this.name = '';
				this.email = '';
				this.username = '';
				this.$store.commit('SET_NOTIFICATION', {
					type: 'success',
					title: 'Success',
					message: 'Check your email to set password.'
				});
			}).catch(error => {
				this.$store.commit('SET_LOADING_STATUS', false);
				if (error.response && error.response.data.message) {
					this.$store.commit('SET_NOTIFICATION', {
						type: 'error',
						message: error.response.data.message
					})
				}
				if (error.response && error.response.data.errors) {
					this.errors = error.response.data.errors;
				}
			})
		}
	}
}
</script>

<style lang="scss">
@import "~shapla-color-system/src/variables";

.form-control--terms {
	display: flex;

	.shapla-checkbox {
		max-width: 24px;
		flex-grow: 0;
	}

	a {
		color: $primary;
	}
}
</style>
