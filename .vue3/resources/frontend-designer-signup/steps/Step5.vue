<script setup lang="ts">
defineProps({
  emailAddress: {type: String, default: ''}
})

const homeUrl = window.StackonetToolkit.homeUrl;
const lostPasswordUrl = window.StackonetToolkit.lostPasswordUrl;
</script>

<template>
  <div class="signup">
    <div class="signup__subtitle text-center mb-8 max-w-[600px] mx-auto">
      <h3 class="mt-6 mb-4 text-xl font-bold">
        Step 5 - <strong class="text-primary">Verify your email address</strong>
      </h3>
      <p class="text-lg">
        We have sent mail to your email address <span class="text-primary font-bold">{{ emailAddress }}</span> to set
        your account password. If you don't see the email, wait few minutes and also check on spam folder on your mail.
      </p>
      <p class="mt-4">
        If you did not get email even after waiting few minutes, click the button below to re-send email.
      </p>
      <div class="flex space-x-4 justify-center items-center">
        <a :href="homeUrl" class="shapla-button is-primary is-outline">Back to Home page</a>
        <a :href="lostPasswordUrl" class="shapla-button is-primary">Re-send Email</a>
      </div>
    </div>
  </div>
</template>
