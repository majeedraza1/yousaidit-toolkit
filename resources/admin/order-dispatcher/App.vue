<template>
  <div class="yousaiditcard_admin_order_dispatcher">
    <div class="wrap">
      <router-view/>
    </div>
  </div>
</template>

<script>

export default {
  name: "App",
}
</script>

<style lang="scss">
.yousaiditcard_admin_order_dispatcher {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }
}
</style>
