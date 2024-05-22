<script lang="ts">
import {defineComponent} from 'vue'
import {column, columns, shaplaButton} from 'shapla-vue-components'
import ReportCard from "@/frontend/designers/components/ReportCard.vue";

export default defineComponent({
  name: "AddNewCard",
  components: {column, columns, ReportCard, shaplaButton}
})
</script>

<template>
  <div>
    <columns multiline>
      <column :tablet="6" :desktop="4" :widescreen="3">
        <div class="yousaidit-report-card">
          <div class="yousaidit-report-card__content">
            some data
          </div>
          <div class="yousaidit-report-card__actions">
            <div class="yousaidit-report-card__title">Standard Card</div>
          </div>
        </div>
      </column>
    </columns>
  </div>
</template>
