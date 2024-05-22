<template>
  <div class="yousaidit_admin_order_dispatcher--dispatcher">
    <h1 class="wp-heading-inline">Order Dispatcher</h1>
    <hr class="wp-header-end">

    <div class="button-container">
      <ShaplaColumns>
        <ShaplaColumn :tablet="4">
          <ShaplaButton theme="primary" size="large" fullwidth @click="goToRoute('PrintCards')">
            Print Cards
          </ShaplaButton>
        </ShaplaColumn>
        <ShaplaColumn :tablet="4">
          <ShaplaButton theme="primary" size="large" fullwidth @click="goToRoute('DispatchOrders')">
            Dispatch Orders
          </ShaplaButton>
        </ShaplaColumn>
        <ShaplaColumn :tablet="4">
          <ShaplaButton theme="primary" size="large" fullwidth @click="goToRoute('CompleteOrders')">
            Complete Orders
          </ShaplaButton>
        </ShaplaColumn>
      </ShaplaColumns>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaColumn, ShaplaColumns} from '@shapla/vue-components';
import {useRouter} from "vue-router";

const router = useRouter();

const goToRoute = (name: string) => {
  router.push({name: name});
}
</script>
