<template>
  <div class="wrap">
    <div class="reminders-container">
      <router-view/>
      <confirm-dialog/>
      <notification :options="notification"/>
      <spinner :active="loading"/>
    </div>
  </div>
</template>

<script>
import {mapState} from 'vuex';
import {ConfirmDialog, notification, spinner} from 'shapla-vue-components';

export default {
  name: "App",
  components: {spinner, notification, ConfirmDialog},
  computed: {
    ...mapState(['loading', 'notification']),
  }
}
</script>

<style lang="scss">
.reminders-container {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }
}
</style>
