<template>
	<div class="swiper-slide p-2">
		<div :style="itemStyles" class="swiper-slide-inner shadow-xl">
			<slot v-bind:sizes="cardSizes"/>
		</div>
	</div>
</template>

<script>
const availableCardSizes = window.StackonetToolkit.pdfSizes
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
		cardSizes() {
			return {width: this.width, height: this.height}
		},
		itemStyles() {
			return {
				width: `${this.width}px`,
				"--item-width": `${this.width}px`,
				"--item-height": `${this.height}px`,
			}
		}
	},
	methods: {
		calculateWidthAndHeight() {
			let innerEL = this.$el.querySelector('.swiper-slide-inner');
			let d = this.card_dimension;

			if (document.body.offsetWidth < 768) {
				this.width = this.$el.offsetWidth;
				this.height = Math.round(this.width * (d[1] / d[0]));
			} else {
				this.height = innerEL.offsetHeight;
				this.width = Math.round(innerEL.offsetHeight * (d[0] / d[1]));
			}
		}
	},
	mounted() {
		setTimeout(() => {
			this.calculateWidthAndHeight();
		});
		window.addEventListener('resize', () => {
			this.calculateWidthAndHeight();
		})
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
