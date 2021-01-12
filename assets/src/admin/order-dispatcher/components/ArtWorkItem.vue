<template>
	<div v-if="product.product_sku" class="artwork-info flex items-center justify-start my-1 space-x-2">
		<a class="artwork-info__title" :href="product.edit_product_url" :title="product.title">
			{{ product.title }}
		</a>
		<span class="artwork-info__sku">({{ product.product_sku }})</span>
		<span class="artwork-info__qty">(Qty: {{ product.quantity }})</span>
		<a class="artwork-info__icon-pdf" target="_blank" v-if="product.art_work.url" :href="product.art_work.url"
		   title="Download Card PDF">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path fill="none" d="M0 0h24v24H0z"/>
				<path
					d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z"/>
			</svg>
		</a>
		<span class="artwork-info__icon-chat" v-if="product.has_inner_message" :title="product.inner_message">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path fill="none" d="M0 0h24v24H0z"/>
				<path
					d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
			</svg>
		</span>
	</div>
</template>

<script>
export default {
	name: "ArtWorkItem",
	props: {
		product: {type: Object}
	}
}
</script>

<style lang="scss" scoped>
.artwork-info {
	&__title {
		max-width: 200px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	&__icon-pdf,
	&__icon-chat {
		align-items: center;
		display: inline-flex;
		height: 1.5em;
		justify-content: center;
		width: 1.5em;

		svg {
			height: 1em;
			width: 1em;
		}
	}
}
</style>
