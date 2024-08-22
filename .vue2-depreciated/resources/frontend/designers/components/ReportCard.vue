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

<script>
	export default {
		name: "ReportCard",
		props: {
			title: {type: String, default: ''},
			content: {type: [String, Number], default: ''},
			backgroundColor: {type: String},
		},
		computed: {
			cardClasses() {
				return {
					'has-custom-color': this.backgroundColor,
				}
			}
		}
	}
</script>

<style lang="scss">
	@import "~shapla-color-system/src/variables";

	.yousaidit-report-card {
		border-radius: 4px;

		&:not(.has-custom-color) {
			background: $primary;
			color: $on-primary;
		}

		&.has-custom-color {
			background: var(--card-background-color, $primary);
		}

		&__content {
			font-size: 2.5em;
			padding: 20px;
			text-align: center;
			font-weight: 500;
		}

		&__actions {
			background: rgba(#fff, 0.6);
			padding: 15px;
			text-align: center;
		}
	}
</style>
