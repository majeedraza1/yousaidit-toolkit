<template>
  <div class="has-registration-form">
    <form @submit.prevent="submitForm" class="stackonet-support-ticket-login-form">
      <div class="form-control">
        <ShaplaInput
            label="Name"
            autocomplete="name"
            v-model="state.name"
            :has-error="hasNameError"
            :validation-text="state.errors.name?state.errors.name[0]:''"
        />
      </div>
      <div class="form-control">
        <ShaplaInput
            type="email"
            label="Email"
            autocomplete="email"
            v-model="state.email"
            :has-error="hasEmailError"
            :validation-text="state.errors.email?state.errors.email[0]:''"
        />
      </div>
      <div class="form-control">
        <ShaplaInput
            label="Username"
            autocomplete="username"
            v-model="state.username"
            :has-error="hasUsernameError"
            :validation-text="state.errors.username?state.errors.username[0]:''"
        />
      </div>
      <div class="form-control form-control--terms">
        <ShaplaCheckbox v-model="state.accept_terms"/>
        <span>
					I agree to the
					<a target="_blank" :href="termsUrl">Terms of Service</a>
					and
					<a target="_blank" :href="privacyPolicyUrl">Privacy Policy</a>.
				</span>
      </div>
      <div>
        <ShaplaButton theme="primary" :fullwidth="true" :disabled="!canSubmit">Log in</ShaplaButton>
      </div>
    </form>
  </div>
</template>

<script lang="ts" setup>
import axios from "../../utils/axios";
import {ShaplaButton, ShaplaCheckbox, ShaplaInput} from '@shapla/vue-components';
import {Notify, Spinner} from "@shapla/vanilla-components";
import {computed, reactive} from "vue";

const state = reactive({
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
})

const submitForm = () => {
  Spinner.show();
  axios.post('registration', {
    name: state.name,
    email: state.email,
    username: state.username,
  }).then(() => {
    Spinner.hide();
    state.errors = {username: [], email: [], name: []};
    state.name = '';
    state.email = '';
    state.username = '';
    Notify.success('Check your email to set password.', 'Success!')
  }).catch(error => {
    Spinner.hide();
    if (error.response && error.response.data.message) {
      Notify.error(error.response.data.message, 'Error!');
    }
    if (error.response && error.response.data.errors) {
      state.errors = error.response.data.errors;
    }
  })
}

const privacyPolicyUrl = computed(() => window.DesignerProfile.privacyPolicyUrl)
const termsUrl = computed(() => window.DesignerProfile.termsUrl)
const isValidEmail = computed(() => {
  let re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return state.name.length && re.test(state.email);
})
const canSubmit = computed(() => !!(state.username.length >= 4 && state.name.length >= 3 && state.accept_terms && isValidEmail))
const hasEmailError = computed(() => !!(state.errors.email && state.errors.email.length))
const hasUsernameError = computed(() => !!(state.errors.username && state.errors.username.length))
const hasNameError = computed(() => !!(state.errors.name && state.errors.name.length))
</script>

<style lang="scss">
@use "shapla-css/src/index.scss" as shapla;

.form-control--terms {
  display: flex;

  .shapla-checkbox {
    max-width: 24px;
    flex-grow: 0;
  }

  a {
    color: shapla.$primary;
  }
}
</style>
