<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import {BrandInfoInterface} from "../interfaces.ts";
import {computed, reactive} from "vue";

interface StateInterface extends BrandInfoInterface {

}


const emit = defineEmits<{
  submit: [value: BrandInfoInterface]
}>()
const state = reactive<StateInterface>({
  brand_name: '',
  brand_location: '',
  brand_profile_url: '',
  brand_instagram_url: '',
  brand_details: '',
})

const canSubmit = computed(() => {
  return !!(
      state.brand_name.length >= 2 &&
      state.brand_location.length >= 3 &&
      state.brand_details.length >= 3 &&
      state.brand_profile_url.length >= 3
  )
})
const onSubmit = () => emit('submit', state);
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
        <ShaplaColumns multiline column-gap="1.5rem">
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
                   class="yousaidit-login-modal__input" v-model="state.brand_profile_url">
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
        <ShaplaButton theme="primary" class="px-10 font-bold" :disabled="!canSubmit" @click.prevent="onSubmit">Next
        </ShaplaButton>
      </div>
    </div>
  </div>
</template>
