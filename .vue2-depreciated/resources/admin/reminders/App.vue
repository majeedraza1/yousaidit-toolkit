<template>
  <div class="wrap">
    <div class="reminders-container">
      <router-view/>
    </div>
  </div>
</template>

<script>
export default {
  name: "App"
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
