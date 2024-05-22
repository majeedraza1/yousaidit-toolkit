<script>
import {defineComponent} from 'vue'

export default defineComponent({
  name: "App",
})
</script>

<template>
  <div class="wrap">
    <div class="font-manager-container border-box-all">
      <router-view/>
    </div>
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
