<template>
	<div class="yousaidit-designer-card" v-if="item">
		<div class="yousaidit-designer-card__actions-top">
			<div class="yousaidit-designer-card__comments" @click="$emit('click:comments',item)"
			     v-if="item.comments_count >0">
				<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
					<path
						d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
					<path d="M0 0h24v24H0z" fill="none"/>
				</svg>
			</div>
			<div class="yousaidit-designer-card__settings" @click="$emit('click:settings',item)"
			     v-if="item.status === 'accepted'">
				<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24"
				     viewBox="0 0 24 24"
				     width="24">
					<g>
						<path d="M0,0h24v24H0V0z" fill="none"/>
						<path
							d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
					</g>
				</svg>
			</div>
			<div class="yousaidit-designer-card__delete" @click="handleDeleteItem(item)"
			     v-if="item.status === 'rejected'">
				<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
					<path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5l-1-1h-5l-1 1H5v2h14V4z"/>
					<path d="M0 0h24v24H0V0z" fill="none"/>
				</svg>
			</div>
		</div>
		<div :class="`yousaidit-designer-card__status status-${item.status}`">
			{{ item.status }}
		</div>
		<div class="yousaidit-designer-card__image">
			<image-container :width-ratio="item.image.width" :height-ratio="item.image.height">
				<img class="yousaidit-designer-profile-card__image" :src="item.image.url" :alt="item.image.title">
			</image-container>
		</div>
		<div class="yousaidit-designer-card__spacer"></div>
		<div class="yousaidit-designer-card__actions">
			<div class="yousaidit-designer-card__title">{{ item.card_title }}</div>
			<div class="yousaidit-designer-card__dropdown-icon">
				<icon-container hoverable>
					<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
						<path v-if="!menuActive" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
						<path v-if="menuActive" d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
						<path d="M0 0h24v24H0z" fill="none"/>
					</svg>
				</icon-container>
			</div>

			<dropdown-menu :active="menuActive">
				<div class="shapla-dropdown-item">
					<icon-container>
						<svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
							<use xlink:href="#icon-svg-print"></use>
						</svg>
					</icon-container>
					<strong>Sizes:</strong>
					<div>
						<shapla-chip v-for="_size in item.sizes" small :key="_size.id">{{ _size.title }}</shapla-chip>
					</div>
				</div>
				<span class="shapla-dropdown-divider"></span>
				<div class="shapla-dropdown-item">
					<icon-container>
						<svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
							<use xlink:href="#icon-svg-category"></use>
						</svg>
					</icon-container>
					<strong>Category:</strong>
					<div>
						<shapla-chip v-for="tag in item.categories" small :key="tag.id">{{ tag.title }}</shapla-chip>
					</div>
				</div>
				<span class="shapla-dropdown-divider"></span>
				<div class="shapla-dropdown-item">
					<icon-container>
						<svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
							<use xlink:href="#icon-svg-tags"></use>
						</svg>
					</icon-container>
					<strong>Tags:</strong>
					<div>
						<shapla-chip v-for="tag in item.tags" :key="tag.id">{{ tag.title }}</shapla-chip>
					</div>
				</div>
			</dropdown-menu>
		</div>
	</div>
</template>

<script>
import {imageContainer, iconContainer, shaplaChip} from 'shapla-vue-components';
import {dropdownMenu} from 'shapla-dropdown';

export default {
	name: "CardItem",
	components: {imageContainer, iconContainer, dropdownMenu, shaplaChip},
	props: {
		item: {type: Object}
	},
	data() {
		return {
			menuActive: false,
		}
	},
	mounted() {
		document.addEventListener('click', event => {
			let cardDropdown = this.$el.querySelector('.yousaidit-designer-card__actions');
			let cardDropdownIcon = this.$el.querySelector('.yousaidit-designer-card__dropdown-icon');

			let isIconClick = cardDropdownIcon.contains(event.target);
			let dropdownClick = this.menuActive && !cardDropdown.contains(event.target);

			if (isIconClick) {
				this.menuActive = true;
			}

			if (dropdownClick) {
				this.menuActive = false;
			}
		});
	},
	methods: {
		handleDeleteItem(item) {
			this.$dialog.confirm('Are you sure to delete?').then(confirmed => {
				if (confirmed) {
					this.$emit('click:delete', item);
				}
			})
		}
	}
}
</script>

<style lang="scss">
@import "~shapla-color-system/src/variables";

.yousaidit-designer-card {
	box-shadow: 0 2px 2px 0 rgba(#000, .14), 0 3px 1px -2px rgba(#000, .2), 0 1px 5px 0 rgba(#000, .12);
	display: flex;
	flex-direction: column;
	height: 100%;
	position: relative;

	&__actions-top {
		display: flex;
		left: .5rem;
		position: absolute;
		top: .5rem;

		> * {
			margin-right: 5px;
			width: 2em;
			height: 2em;
			background: #f1f1f1;
			padding: 5px;
			border-radius: 999px;
			display: flex;
			justify-content: center;
			align-items: center;
			z-index: 2;

			svg {
				fill: $text-icon;
			}

			&:hover {
				background: #ddd;
				cursor: pointer;
			}
		}
	}

	&__comments,
	&__settings,
	&__delete {
	}

	&__delete {
		background: $error;
		color: $on-error;

		&:hover {
			background: $error;
			color: $on-error;
		}

		svg {
			fill: currentColor;
		}
	}

	&__status {
		border-radius: 4px;
		background: $secondary;
		color: $on-secondary;
		display: inline-flex;
		padding: 0.25rem;
		position: absolute;
		top: 5px;
		right: 5px;
		z-index: 1;

		&.status-accepted {
			background: $success;
			color: $on-success;
		}

		&.status-rejected {
			background: $error;
			color: $on-error;
		}
	}

	&__spacer {
		flex-grow: 1;
	}

	&__actions {
		width: 100%;
		background-color: transparent;
		padding: 8px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		position: relative;
	}

	.shapla-dropdown[aria-expanded="true"] {
		path.icon-down {
			display: none;
		}
	}

	.shapla-dropdown-menu {
		width: 100%;
	}

	.shapla-dropdown-item {
		display: flex;
		font-size: 1rem;

		> * {
			margin-right: 5px;
		}
	}
}
</style>
