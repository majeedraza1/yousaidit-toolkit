<template>
	<div class="pdf-card-item">
		<div class="pdf-card-item__header">{{headerText}}</div>
		<div class="pdf-card-item__content">
			<div class="pdf-card-item__item" v-for="_pdf in items">
				<div class="pdf-card-item__title" v-html="_pdf[titleKey]"></div>
				<div class="pdf-card-item__link">
					<span v-html="_pdf[urlKey]"></span>
					<icon-container>
						<a class="shapla-icon" target="_blank" :href="_pdf[urlKey]" title="View File">
							<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
								<path d="M0 0h24v24H0z" fill="none"/>
								<path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/>
							</svg>
						</a>
					</icon-container>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import iconContainer from 'shapla-icon-container';

	export default {
		name: "PdfCardItem",
		components: {iconContainer},
		props: {
			headerText: {type: String, default: ''},
			items: {type: Array, default: () => []},
			titleKey: {type: String, default: 'title'},
			urlKey: {type: String, default: 'url'},
		}
	}
</script>

<style lang="scss">
	@import "~shapla-color-system/src/variables";

	.pdf-card-item {
		border: 1px solid rgba(#000, 0.12);
		border-radius: 4px;
		margin-bottom: 10px;


		&__header {
			background-color: rgba(#000, 0.04);
			padding: 8px;
		}

		&__content {
			padding: 8px;
			color: $text-secondary;

			svg {
				fill: currentColor;
			}
		}

		&__item:not(:last-child) {
			border-bottom: 1px solid rgba(#000, 0.12);
			margin-bottom: 10px;
			padding-bottom: 10px;
		}

		&__link {
			display: flex;
			align-items: center;
			justify-content: space-between;
		}
	}
</style>
