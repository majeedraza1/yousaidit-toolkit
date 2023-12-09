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
import axios from "@/utils/axios";
import {mapGetters} from 'vuex';
import {shaplaButton, shaplaCheckbox, textField} from 'shapla-vue-components';
import {Notify, Spinner} from "@shapla/vanilla-components";

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
    privacyPolicyUrl() {
      return window.DesignerProfile.privacyPolicyUrl;
    },
    termsUrl() {
      return window.DesignerProfile.termsUrl;
    },
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
      Spinner.show();
      axios.post('registration', {
        name: this.name,
        email: this.email,
        username: this.username,
      }).then(() => {
        Spinner.hide();
        this.errors = {username: [], email: [], name: []};
        this.name = '';
        this.email = '';
        this.username = '';
        Notify.success('Check your email to set password.', 'Success!')
      }).catch(error => {
        Spinner.hide();
        if (error.response && error.response.data.message) {
          Notify.error(error.response.data.message, 'Error!');
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
