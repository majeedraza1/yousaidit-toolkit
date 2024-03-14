<script setup lang="ts">
import {reactive} from "vue";
import Step1 from "./steps/Step1.vue";
import Step2 from "./steps/Step2.vue";
import Step3 from "./steps/Step3.vue";
import {BrandInfoInterface, LoginInfoInterface} from "./interfaces.ts";
import Step4 from "./steps/Step4.vue";

const state = reactive({
  currentStep: 4,
  name: '',
  email: '',
  password: '',
  brand_name: '',
  brand_location: '',
  brand_profile_url: '',
  brand_instagram_url: '',
  brand_details: '',
  paypal_email: '',
})

const onStepOneDone = (data: LoginInfoInterface) => {
  state.name = data.name;
  state.email = data.email;
  state.password = data.password;
  state.currentStep = 2;
}

const onStepTwoDone = (data: BrandInfoInterface) => {
  state.brand_name = data.brand_name;
  state.brand_location = data.brand_location;
  state.brand_profile_url = data.brand_profile_url;
  state.brand_instagram_url = data.brand_instagram_url;
  state.brand_details = data.brand_details;
  state.currentStep = 3;
}
const onStepThreeDone = (email: string) => {
  state.paypal_email = email;
  state.currentStep = 4;
}
const onStepFourDone = (data: FormData) => {
  for (const [key, value] of Object.entries(state)) {
    if ('currentStep' !== key) {
      data.append(key, value.toString());
    }
  }

  for (let pair of data.entries()) {
    console.log(pair[0] + ', ' + pair[1]);
  }
}
</script>

<template>
  <Step1 v-if="state.currentStep === 1" @submit="onStepOneDone"/>
  <Step2 v-if="state.currentStep === 2" @submit="onStepTwoDone"/>
  <Step3 v-if="state.currentStep === 3" @submit="onStepThreeDone"/>
  <Step4 v-if="state.currentStep === 4" @submit="onStepFourDone"/>
</template>
