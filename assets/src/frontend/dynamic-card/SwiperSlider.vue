<template>
	<div class="swiper">
		<div class="swiper-wrapper">
			<swiper-slider-item :card_size="card_size">
				<slot name="canvas">Design Card canvas</slot>
			</swiper-slider-item>
			<swiper-slider-item :card_size="card_size">
				<slot name="inner-message">Inner message canvas</slot>
			</swiper-slider-item>
		</div>
	</div>
</template>

<script>
import 'swiper/css';
import 'swiper/css/pagination';
import Swiper, {Navigation} from 'swiper';
import SwiperSliderItem from "@/frontend/dynamic-card/SwiperSliderItem";

export default {
	name: "SwiperSlider",
	components: {SwiperSliderItem},
	props: {
		card_size: {type: String, default: ''},
		slideTo: {type: Number, default: 0}
	},
	data() {
		return {
			swiper: null
		}
	},
	watch: {
		slideTo(newValue) {
			if (this.swiper) {
				this.swiper.slideTo(newValue);
			}
		}
	},
	mounted() {
		Swiper.use([Navigation]);
		const element = this.$el;
		this.swiper = new Swiper(element, {
			navigation: {
				nextEl: element.querySelector('.swiper-button-next'),
				prevEl: element.querySelector('.swiper-button-prev'),
			},
			slidesPerView: 1,
			spaceBetween: 10,
			centeredSlides: true,
			breakpoints: {
				800: {
					slidesPerView: 'auto',
				}
			}
		});
	}
}
</script>

<style>

</style>
