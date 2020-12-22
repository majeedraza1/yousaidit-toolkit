<template>
	<div class="pdf-image-item" :class="{'is-multiple':isMultiple}">
		<div class="pdf-image-item__items">
			<template v-if="isMultiple && Array.isArray(images)">
				<div class="pdf-image-item__item" v-for="(_img,index) in images" :key="index">
					<image-container :width-ratio="_img[widthKey]" :height-ratio="_img[heightKey]">
						<img :src="_img[urlKey]" alt="">
					</image-container>
				</div>
			</template>
			<template v-if="!isMultiple">
				<div class="pdf-image-item__item">
					<image-container :width-ratio="images[widthKey]" :height-ratio="images[heightKey]">
						<img :src="images[urlKey]" alt="">
					</image-container>
				</div>
			</template>
		</div>
	</div>
</template>

<script>
	import imageContainer from 'shapla-image-container';

	export default {
		name: "PdfImageItem",
		components: {imageContainer},
		props: {
			isMultiple: {type: Boolean, default: true},
			images: {type: [Array, Object], default: () => []},
			urlKey: {type: String, default: 'url'},
			widthKey: {type: String, default: 'width'},
			heightKey: {type: String, default: 'height'},
		}
	}
</script>

<style lang="scss">
	.pdf-image-item {

		&__items {
			display: flex;
			flex-wrap: wrap;
			margin: -5px;
		}

		&__item {
			padding: 5px;
			width: 150px;
			display: block;
			float: left
		}

		&:not(.is-multiple) {
			.pdf-image-item__item {
				width: 300px;
			}
		}
	}
</style>
