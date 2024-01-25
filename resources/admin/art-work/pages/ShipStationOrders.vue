<template>
  <div class="wrap stackonet-orders-list-table">
    <h1 class="wp-heading-inline">ShipStation Orders</h1>
    <hr class="wp-header-end">
    <orders/>
  </div>
</template>

<script>
import Orders from "../../order-dispatcher/components/Orders";

export default {
  name: "ShipStationOrders",
  components: {Orders},
}
</script>

<style lang="scss">
.stackonet-orders-list-table {
  .row-actions {
    display: none !important;
  }

  .select--pdf-size {
    height: 2.8em;
  }

  .yousaidit-loop-product {
    align-items: center;
    display: flex;
    justify-content: flex-start;

    svg {
      width: 1.5em;
      height: 1.5em;
      fill: currentColor;
    }
  }
}
</style>
