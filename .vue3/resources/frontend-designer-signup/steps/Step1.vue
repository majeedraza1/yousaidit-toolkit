<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from "@shapla/vue-components";
import InputPassword from "./InputPassword.vue";
import {computed, reactive} from "vue";
import {LoginInfoInterface} from "../interfaces.ts";

const emit = defineEmits<{
  submit: [value: LoginInfoInterface]
}>()
const state = reactive<LoginInfoInterface>({
  name: '',
  email: '',
  password: '',
})

const canSubmit = computed(() => state.name.length >= 2 && state.email.length >= 3 && state.password.length >= 8)
const onSubmit = () => emit('submit', state);
</script>

<template>
  <div class="signup">
    <div class="signup__title text-center">
      <h2 class="text-3xl font-bold mb-0">
        Sign Up to <strong class="text-primary">Become</strong> a <strong class="text-primary">You Said It</strong>
        designer
      </h2>
      <hr class="w-48 border-primary mb-4">
      <p>Fill out the form below to create your free account.</p>
      <p>
        Already have an account ?
        <span class="signup__title__anchor text-primary cursor-pointer" data-target="login-modal">Sign In</span>
      </p>
    </div>
    <div class="signup__subtitle text-center mb-8">
      <h3 class="mt-6 text-xl font-bold">
        Step 1 - <strong class="text-primary">Login Details</strong>
      </h3>
      <p>Lets get started, first lets create your new account</p>
    </div>
    <div class="signup__body">
      <form action="" class="lg:px-16">
        <ShaplaColumns multiline column-gap="1.5rem">
          <ShaplaColumn :tablet="12" :desktop="6">
            <label for="name">name</label>
            <input id="name" type="text" placeholder="Enter Name" class="yousaidit-login-modal__input"
                   v-model="state.name">
          </ShaplaColumn>
          <ShaplaColumn :tablet="12" :desktop="6">
            <label for="email">Email</label>
            <input id="email" type="email" placeholder="Enter Email" class="yousaidit-login-modal__input"
                   v-model="state.email">
          </ShaplaColumn>
          <ShaplaColumn :tablet="12" :desktop="6">
            <label for="password">password</label>
            <InputPassword v-model="state.password"/>
            <p class="text-sm mt-2">Minimum 8 characters. Combine with uppercase latter, number and special
              characters.</p>
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
