<template>
  <div class="wrap">
    <div class="tree-planting-container">
      <router-view/>
      <confirm-dialog/>
      <notification :options="notification"/>
      <spinner :active="loading"/>
    </div>
  </div>
</template>

<script>
import {ConfirmDialog, notification, spinner} from 'shapla-vue-components';

export default {
  name: "App",
  components: {spinner, notification, ConfirmDialog},
  data() {
    return {
      loading: false,
      notification: {}
    }
  }
}
</script>

<style lang="scss">
.tree-planting-container {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }
}
</style>
