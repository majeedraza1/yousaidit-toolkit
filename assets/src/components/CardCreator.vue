<template>
	<modal :active="active" title="Card Design Creator" content-size="full" @close="$emit('close')">
		<template v-if="!has_card_size">
			<h1 class="text-center">Card size</h1>
			<p class="text-center">Choose card size.</p>
		</template>
		<columns v-if="!card_size.length">
			<column :tablet="6" class="md:flex items-center justify-end">
				<div @click="card_size = 'square'"
					 class="border border-solid border-gray-200 w-36 h-36 flex items-center justify-center bg-gray-100 cursor-pointer"
					 :class="{'border-primary':card_size === 'square'}"
				>
					<div class="text-lg">Square</div>
				</div>
			</column>
			<column :tablet="6" class="md:flex items-center justify-start">
				<div @click="card_size = 'a'"
					 class="border border-solid border-gray-200 w-36 h-44 flex flex-col items-center justify-center bg-gray-100 cursor-pointer"
					 :class="{'border-primary':card_size === 'a'}"
				>
					<div class="text-lg">A Size</div>
					<div class="text-sm">(A6 & A5)</div>
				</div>
			</column>
		</columns>
		<columns v-if="card_size === 'a'">
			<column :tablet="6" class="md:flex items-center justify-end">
				<div @click="card_size = 'a5'"
					 class="border border-solid border-gray-200 w-36 h-44 flex flex-col items-center justify-center bg-gray-100 cursor-pointer"
					 :class="{'border-primary':card_size === 'a5'}"
				>
					<div class="text-lg">A5</div>
				</div>
			</column>
			<column :tablet="6" class="md:flex items-center justify-start">
				<div @click="card_size = 'a6'"
					 class="border border-solid border-gray-200 w-36 h-44 flex flex-col items-center justify-center bg-gray-100 cursor-pointer"
					 :class="{'border-primary':card_size === 'a6'}"
				>
					<div class="text-lg">A6</div>
				</div>
			</column>
		</columns>
		<div class="flex h-full relative" v-show="has_card_size">
			<div :class="`card-canvas card-canvas--${card_size}`" :style="canvas_styles">
				<img class="card-canvas__background" v-if="Object.keys(image).length" :src="image.full.src" alt="">
			</div>
			<div class="p-4" style="max-width: 320px">
				<div>
					<h4 class="font-bold mb-2 mt-0 text-base">Background Image</h4>
					<featured-image @click:add="show_image_modal = true"/>
				</div>
				<div>
					<h4 class="font-bold mb-2 mt-0 text-base">Section</h4>
					<shapla-button @click="show_section_modal = true">Add section</shapla-button>
					<modal :active="show_section_modal" @close="show_section_modal = false" title="Add Section"
						   content-size="small">
						<layer-options/>
					</modal>
				</div>
			</div>
			<media-modal
				v-if="show_image_modal"
				:active="show_image_modal"
				@close="show_image_modal = false"
				:images="images"
				:url="uploadUrl"
				@select:image="handleCardLogoImageId"
				@before:send="addNonceHeader"
				@success="(file,response)=>refreshMediaList(response,'card-logo')"
			/>
		</div>
	</modal>
</template>

<script>
import {modal, columns, column, shaplaButton} from 'shapla-vue-components'
import {FeaturedImage, MediaModal} from "@/shapla/shapla-media-uploader";
import axios from "axios";
import LayerOptions from "@/components/LayerOptions";

export default {
	name: "CardCreator",
	components: {LayerOptions, modal, columns, column, FeaturedImage, MediaModal, shaplaButton},
	props: {
		active: {type: Boolean, default: true}
	},
	emits: ['close'],
	data() {
		return {
			canvas_width: 0,
			show_image_modal: false,
			show_section_modal: false,
			card_size: '',
			card_width: '',
			card_height: '',
			card_sizes: [
				{value: 'a4', label: 'A4 ( 426mm x 303mm )'}
			],
			image: {},
			images: [],
			sections: [],
			activeSection: {}
		}
	},
	computed: {
		has_card_size() {
			return ['square', 'a5', 'a6'].indexOf(this.card_size) !== -1;
		},
		canvas_styles() {
			return {
				'width': `${this.canvas_width}px`
			}
		},
		user() {
			return DesignerProfile.user
		},
		uploadUrl() {
			return window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/attachment';
		}
	},
	watch: {
		card_size(newValue) {
			if (['square', 'a5', 'a6'].indexOf(newValue) !== -1) {
				setTimeout(() => this.canvas_width = this.calculate_canvas_width(), 100);
			}
		}
	},
	methods: {
		calculate_canvas_width() {
			let cardCanvas = this.$el.querySelector('.card-canvas');
			// 300,150
			if (this.card_size === 'square') {
				return cardCanvas.offsetHeight;
			}


			// 303,216
			if (this.card_size === 'a5') {
				return Math.round((303 / 2) / 216 * cardCanvas.offsetHeight);
			}

			// 216,154
			if (this.card_size === 'a6') {
				return Math.round((216 / 2) / 154 * cardCanvas.offsetHeight);
			}
		},
		addNonceHeader(xhr) {
			xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
		},
		handleCardLogoImageId(image) {
			this.image = image;
		},
		refreshMediaList(response, type = 'avatar') {
			let image = response.data.attachment;
			console.log(image, response)
			if ('card_image' === type) {
				// this.update({cover_photo_id: image.id});
				// this.showChangeCoverModal = false;
			}
		},
		getUserUploadedImages() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/attachment', {
				params: {
					mime_types: ['image/jpeg', 'image/png']
				}
			}).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.images = response.data.data;
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			});
		},
	},
	mounted() {
		this.getUserUploadedImages();
		// Test data
		this.card_size = 'a5';
	}
}
</script>

<style lang="scss">
.card-canvas {
	border: 1px dotted rgba(#000, .12);
	display: flex;
	height: 100%;
	position: relative;

	&__background {
		position: absolute;
		width: 100%;
		height: 100%;
	}
}
</style>
