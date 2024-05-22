<template>
  <div class="has-login-form">
    <form @submit.prevent="submitForm" class="stackonet-support-ticket-login-form">
      <div class="form-control">
        <ShaplaInput
            label="Email or Username"
            autocomplete="username"
            v-model="state.user_login"
            :has-error="hasUserLoginError"
            :validation-text="state.errors.user_login?state.errors.user_login[0]:''"
        />
      </div>
      <div class="form-control">
        <ShaplaInput
            type="password"
            label="Password"
            v-model="state.password"
            autocomplete="current-password"
            :has-error="hasPasswordError"
            :validation-text="state.errors.password?state.errors.password[0]:''"
        />
      </div>
      <div class="form-control form-control--remember">
        <div>
          <ShaplaCheckbox v-model="state.remember">Remember me</ShaplaCheckbox>
        </div>
        <div><a :href="lostPasswordUrl">Forgot your password?</a></div>
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
import {Spinner} from "@shapla/vanilla-components";
import {computed, reactive} from "vue";

const state = reactive({
  user_login: '',
  password: '',
  remember: false,
  errors: {
    user_login: [],
    password: [],
  },
})

const submitForm = () => {
  Spinner.show();
  axios.post('web-login', {
    username: state.user_login,
    password: state.password,
    remember: state.remember,
  }).then(() => {
    // Spinner.hide();
    window.location.reload();
  }).catch(error => {
    Spinner.hide();
    if (error.response && error.response.data.errors) {
      state.errors = error.response.data.errors;
    }
  })
}

const lostPasswordUrl = computed(() => window.DesignerProfile.lostPasswordUrl);
const canSubmit = computed(() => !!(state.user_login.length >= 4 && state.password.length >= 4))
const hasUserLoginError = computed(() => !!(state.errors.user_login && state.errors.user_login.length))
const hasPasswordError = computed(() => !!(state.errors.password && state.errors.password.length))
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
