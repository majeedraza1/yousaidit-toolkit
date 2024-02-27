<template>
	<div class="yousaidit-report-card" :class="cardClasses" :style="`--card-background-color:${backgroundColor}`">
		<div class="yousaidit-report-card__content">
			<slot>
				<div v-html="content"></div>
			</slot>
		</div>
		<div class="yousaidit-report-card__actions">
			<div class="yousaidit-report-card__title" v-html="title"></div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import {computed} from "vue";

const props = defineProps({
  title: {type: String, default: ''},
  content: {type: [String, Number], default: ''},
  backgroundColor: {type: String},
})
const cardClasses = computed(() =>{
  return {
    'has-custom-color': props.backgroundColor,
  }
})
</script>
