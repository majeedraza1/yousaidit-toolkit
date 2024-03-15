<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import {BrandInfoInterface} from "../interfaces.ts";
import {computed, reactive} from "vue";
import {validateUsernameFromServer} from "../store.ts";

interface StateInterface extends BrandInfoInterface {
  loading: boolean,
  usernameErrors: string[],
}

const profileBaseUrl = window.StackonetToolkit.designerProfileBaseUrl;

const emit = defineEmits<{
  submit: [value: BrandInfoInterface]
}>()
const state = reactive<StateInterface>({
  brand_name: '',
  brand_location: '',
  username: '',
  brand_instagram_url: '',
  brand_details: '',
  loading: false,
  usernameErrors: [],
})

const canSubmit = computed(() => {
  return !!(
      state.usernameErrors.length === 0 &&
      state.brand_name.length >= 2 &&
      state.brand_location.length >= 3 &&
      state.brand_details.length >= 3 &&
      state.username.length >= 4
  )
})
const onSubmit = () => {
  state.loading = true;
  validateUsernameFromServer(state.username)
      .then(() => {
        emit('submit', state)
      })
      .catch(errors => {
        state.usernameErrors = errors;
      })
      .finally(() => {
        state.loading = false;
      })
}
const onChangeUsername = () => {
  if (state.usernameErrors.length) {
    state.usernameErrors = [];
  }
}
</script>

<template>
  <div class="signup">
    <div class="signup__subtitle text-center mb-8">
      <h3 class="mt-6 text-xl font-bold">
        Step 2 - <strong class="text-primary">About Your Brand</strong>
      </h3>
      <p>Tell us and our customers little about you and your brand, this will display on your profile page.</p>
    </div>
    <div class="signup__body">
      <form action="">
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="12" :desktop="6">
            <label for="name">Brand Name</label>
            <input id="name" type="text" placeholder="Enter Brand Name" class="yousaidit-login-modal__input"
                   v-model="state.brand_name">
          </ShaplaColumn>
          <ShaplaColumn :tablet="12" :desktop="6">
            <label for="location">location</label>
            <input id="location" type="text" placeholder="Enter Location e.g Carlisle, UK"
                   class="yousaidit-login-modal__input" v-model="state.brand_location">
          </ShaplaColumn>
          <ShaplaColumn :tablet="12" :desktop="6">
            <label for="username">Choose Brand Profile URL</label>
            <input id="username" type="text" placeholder="Lower Case Text Only and No Spaces"
                   class="yousaidit-login-modal__input" v-model="state.username"
                   :class="{'has-error':state.usernameErrors.length}" @input="onChangeUsername">
            <p class="text-sm mt-1 text-red-600" v-if="state.usernameErrors.length">{{ state.usernameErrors[0] }}</p>
            <p class="text-sm mt-1">Unique in our system. Only lower case text (a-z), dash (-) and number (0-9) are
              allowed.</p>
            <p class="text-sm mt-1 mb-0 font-bold">
              {{ profileBaseUrl }}/<span class="text-primary">{{ state.username }}</span>
            </p>
          </ShaplaColumn>
          <ShaplaColumn :tablet="12" :desktop="6">
            <label for="instagramURL">Instagram URL</label>
            <input id="instagramURL" type="text" placeholder="https://www.instagram.com/username"
                   class="yousaidit-login-modal__input" v-model="state.brand_instagram_url">
          </ShaplaColumn>
          <ShaplaColumn :tablet="12">
            <textarea name="aboutMe" id="aboutMe" class="yousaidit-login-modal__input" rows="5"
                      placeholder="Tell us a little about your brand..." v-model="state.brand_details"></textarea>
          </ShaplaColumn>

        </ShaplaColumns>
      </form>
      <div class="mt-16 text-center">
        <ShaplaButton theme="primary" class="px-10 font-bold" :disabled="!canSubmit" @click.prevent="onSubmit"
                      :loading="state.loading">Next
        </ShaplaButton>
      </div>
    </div>
  </div>
</template>
