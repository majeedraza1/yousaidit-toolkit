<template>
  <div class="yousaiditcard_admin_designer_app">
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
.yousaiditcard_admin_designer_app {
  position: relative;
  box-sizing: border-box;

  * {
    box-sizing: border-box;
  }

  .shapla-search-form__input {
    padding: .5em 2.5em .5em .5em !important;
  }
}
</style>
