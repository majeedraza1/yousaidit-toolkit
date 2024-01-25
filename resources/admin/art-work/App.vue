<template>
  <div class="repair-art-work-container">
    <router-view/>
  </div>
</template>

<script>

export default {
  name: "App",
}
</script>

<style lang="scss">
.repair-art-work-container {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }
}
</style>
