<template>
  <div class="stackonet_services_dashboard has-login-or-reg-form">
    <div>
      <ShaplaTabs alignment="center">
        <ShaplaTab name="Login" selected>
          <login/>
        </ShaplaTab>
        <ShaplaTab name="Registration">
          <registration/>
        </ShaplaTab>
      </ShaplaTabs>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaTabs, ShaplaTab} from '@shapla/vue-components';
import Login from "./Login.vue";
import Registration from "./Registration.vue";

</script>

<style lang="scss">
.stackonet_services_dashboard.has-login-or-reg-form {
  display: flex;
  justify-content: center;
  align-items: center;
  position: fixed;
  top: 0;
  left: 0;
  background: #fff;
  width: 100%;
  height: 100%;
  z-index: 99;
}
</style>
