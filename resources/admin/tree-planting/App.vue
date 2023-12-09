<template>
  <div class="wrap">
    <div class="tree-planting-container">
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
.tree-planting-container {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }
}
</style>
