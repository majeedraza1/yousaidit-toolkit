<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import {computed, reactive, ref} from "vue";

const emit = defineEmits<{
  submit: [value: FormData]
}>()
const state = reactive({
  newsletter_signup: false,
  accept_terms: false
})
const form = ref<HTMLFormElement>(null)

const canSubmit = computed(() => state.accept_terms === true)
const onSubmit = () => {
  if (form.value) {
    emit('submit', new FormData(form.value))
  }
};
</script>

<template>
  <div class="signup">
    <div class="signup__subtitle text-center mb-8">
      <h3 class="mt-6 text-xl font-bold">
        Step 4 - <strong class="text-primary">Your Bands Logo</strong>
      </h3>
      <p>Upload your brands logo to display on your profile and the back of every card your sell</p>
      <p>You can <span class="text-primary">skip this page</span> and upload your images in the designers dashboard</p>
    </div>
    <div class="signup__body">
      <form ref="form" action="" method="post" @submit.prevent="onSubmit">
        <ShaplaColumns multiline column-gap="1.5rem">
          <ShaplaColumn :tablet="12">
            <label for="name">Profile Logo</label>
            <input type="file" name="profile_logo" class="yousaidit-login-modal__input" accept=".png,.jpg,.jpeg">
            <p class="text-sm mt-1">This file needs a JPG or PNG, Square and a min of <span class="text-primary">250px X 250px</span>
            </p>
          </ShaplaColumn>
          <ShaplaColumn :tablet="12">
            <label for="name">Profile Banner</label>
            <input type="file" name="profile_banner" class="yousaidit-login-modal__input" accept=".png,.jpg,.jpeg">
            <p class="text-sm mt-1">This File Needs A JPG Or PNG And Needs To Be 1397 X 256px</p>
          </ShaplaColumn>
          <ShaplaColumn :tablet="12">
            <label for="name">Back Of Card Logo</label>
            <input type="file" name="card_logo" class="yousaidit-login-modal__input" accept=".png,.jpg,.jpeg">
            <p class="text-sm mt-1">This File Needs A JPG Or PNG And Needs To Be 250px X 250px And Set To 300dpi.</p>
          </ShaplaColumn>
        </ShaplaColumns>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="12">
            <p>Once your account has been created, you'll be able to edit any details in the designers dashboard</p>
          </ShaplaColumn>
          <ShaplaColumn :tablet="12">
            <label for="newsletter_signup">
              <input type="checkbox" name="newsletter_signup" id="newsletter_signup" v-model="state.newsletter_signup">
              <span>Signup to our designers newsletter</span>
            </label>
            <label for="accept_terms">
              <input type="checkbox" name="accept_terms" id="accept_terms" v-model="state.accept_terms">
              <span>I agree to the <a href="" class="text-primary">Terms and Conditions</a></span>
            </label>
          </ShaplaColumn>
        </ShaplaColumns>
        <div>
          <ShaplaButton theme="primary" class="px-10 font-bold" :disabled="!canSubmit">
            Submit to create your new account
          </ShaplaButton>
        </div>
      </form>
    </div>
  </div>
</template>
