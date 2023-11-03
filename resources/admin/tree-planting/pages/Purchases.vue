<script>
import {getPurchases} from '../store.ts'
import {dataTable} from 'shapla-vue-components'

export default {
  name: "Purchases",
  components: {dataTable},
  data() {
    return {
      items: [],
      pagination: {current_page: 1, per_page: 20, total_items: 0}
    }
  },
  mounted() {
    getPurchases().then(data => {
      this.items = data.items;
      this.pagination = data.pagination;
    })
  }
}
</script>

<template>
  <div>
    <h1 class="wp-heading-inline">Purchases</h1>
    <hr class="wp-header-end">
    <div>
      <data-table
          :columns="[{key:'status',label:'Status'}]"
          :items="items"
      />
    </div>
  </div>
</template>
