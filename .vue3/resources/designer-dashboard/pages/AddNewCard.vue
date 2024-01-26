<script lang="ts" setup>
import {ShaplaColumn, ShaplaColumns} from '@shapla/vue-components'
</script>

<template>
  <div>
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="6" :desktop="4" :widescreen="3">
        <div class="yousaidit-report-card">
          <div class="yousaidit-report-card__content">
            some data
          </div>
          <div class="yousaidit-report-card__actions">
            <div class="yousaidit-report-card__title">Standard Card</div>
          </div>
        </div>
      </ShaplaColumn>
    </ShaplaColumns>
  </div>
</template>
