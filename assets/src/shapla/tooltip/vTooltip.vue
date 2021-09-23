<template>
	<div>
		<span class="tooltip-icon">
			<slot name="icon">
				<span ref="icon" class="dashicons dashicons-editor-help"></span>
			</slot>
		</span>
		<div data-tooltip-content style="display: none">
			<slot></slot>
		</div>
	</div>
</template>

<script>
import Tooltip from "./index.js";

export default {
	name: "vTooltip",
	props: {
		tooltip: {type: String},
	},
	mounted() {
		let content = this.$el.querySelector('[data-tooltip-content]');
		new Tooltip(this.$el.querySelector('.tooltip-icon'), {
			theme: 'light',
			content: content.innerHTML || this.tooltip
		});
		content.remove();
	}
}
</script>

<style lang="scss">
@import "index.scss";
</style>
