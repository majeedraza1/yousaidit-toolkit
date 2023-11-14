<script>
import {defineComponent} from 'vue'
import {ConfirmDialog} from 'shapla-vue-components';

export default defineComponent({
  name: "App",
  components: {ConfirmDialog}
})
</script>

<template>
  <div class="font-manager-container border-box-all">
    <router-view/>
    <confirm-dialog/>
  </div>
</template>

<style lang="scss">
.font-manager-container {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }
}
</style>
