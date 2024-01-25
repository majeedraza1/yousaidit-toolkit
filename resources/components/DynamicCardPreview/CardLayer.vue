<template>
	<div class="card-preview-canvas__section" :class="sectionClass">

	</div>
</template>

<script>
export default {
	name: "CardLayer",
	props: {
		active: {type: Boolean},
		showEditIcon: {type: Boolean},
		index: {type: Number},
		section: {type: Object},
	},
	computed: {
		sectionClass() {
			return [
				`section-type--${this.section.section_type}`,
				`section-index--${this.index}`
			];
		}
	}
}
</script>
