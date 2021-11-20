<template>
	<div class="swiper-slide p-2">
		<div :style="itemStyles" class="swiper-slide-inner shadow-xl">
			<slot/>
		</div>
	</div>
</template>

<script>
const availableCardSizes = {
	a4: [426, 303],
	a5: [303, 216],
	a6: [216, 154],
	square: [300, 150],
}
export default {
	name: "SwiperSliderItem",
	props: {
		card_size: {type: String, default: ''},
	},
	data() {
		return {
			height: 0,
			width: 0
		}
	},
	computed: {
		card_dimension() {
			if (Object.keys(availableCardSizes).indexOf(this.card_size) === -1) {
				return [0, 0];
			}
			let dimension = availableCardSizes[this.card_size];
			return [dimension[0] / 2, dimension[1]];
		},
		itemStyles() {
			return {
				// height: `${this.height}px`,
				width: `${this.width}px`,
			}
		}
	},
	methods: {
		calculateWidth(height) {
			let d = this.card_dimension;
			return Math.round(height * (d[0] / d[1]));
		},
		calculateWidthAndHeight() {
			let element = this.$el.querySelector('.swiper-slide-inner');
			this.height = element.offsetHeight;
			this.width = this.calculateWidth(element.offsetHeight);
		}
	},
	mounted() {
		setTimeout(() => {
			this.calculateWidthAndHeight();
		});
	}
}
</script>

<style lang="scss" scoped>
.swiper-slide {
	width: auto;
}

.swiper-slide-inner {
	height: calc(100% - .5rem);
	margin: .25rem 0;
	overflow: hidden;
}
</style>
