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
				<div v-for="(section,index) in sections"
					 class="card-canvas__section"
					 :class="sectionClass(section,index)"
					 :style="sectionStyle(section)"
				>
					<template v-if="section.section_type === 'static-text'">
						{{ section.text }}
					</template>
					<template v-if="section.section_type === 'input-text'">
						{{ section.placeholder }}
					</template>
					<template v-if="section.section_type === 'static-image'">
						<img :src="section.imageOptions.img.src" alt="" :style="sectionImageStyle(section)">
					</template>
					<template v-else>
						<!--						{{ section }}-->
					</template>
				</div>
			</div>
			<div class="p-4" style="max-width: 320px;min-width: 320px">
				<div>
					<template v-for="_card_size in card_sizes" v-if="_card_size.value === card_size">
						{{ _card_size.label }}
					</template>
				</div>
				<div>
					<h4 class="font-bold mb-2 mt-0 text-base">Background Image</h4>
					<featured-image @click:add="show_image_modal = true"/>
				</div>
				<div>
					<h4 class="font-bold mb-2 mt-0 text-base">Section</h4>
					<p>
						<shapla-button @click="show_section_modal = true">Add section</shapla-button>
					</p>
					<!--					<modal :active="show_section_modal" @close="show_section_modal = false" title="Add Section"-->
					<!--						   content-size="small">-->
					<!--					</modal>-->
					<layer-options v-show="show_section_modal" @submit="addSection"/>
				</div>
			</div>
			<div>
				<h4>Sections</h4>
				<div>
					<toggles>
						<toggle v-for="section in sections" :name="section.label" :subtext="section.section_type">
							{{ section }}
						</toggle>
					</toggles>
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
import {modal, columns, column, shaplaButton, toggles, toggle} from 'shapla-vue-components'
import {FeaturedImage, MediaModal} from "@/shapla/shapla-media-uploader";
import axios from "axios";
import LayerOptions from "@/components/LayerOptions";

export default {
	name: "CardCreator",
	components: {LayerOptions, modal, columns, column, FeaturedImage, MediaModal, shaplaButton, toggles, toggle},
	props: {
		active: {type: Boolean, default: true}
	},
	emits: ['close'],
	data() {
		return {
			canvas_width: 0,
			canvas_height: 0,
			show_image_modal: false,
			show_section_modal: false,
			card_size: '',
			card_width: '',
			card_height: '',
			card_sizes: [
				{value: 'a4', width: 426, height: 303, unit: 'mm', label: 'A4 ( 426mm x 303mm )'},
				{value: 'a5', width: 303, height: 216, unit: 'mm', label: 'A5 ( 303mm x 216mm )'},
				{value: 'a6', width: 216, height: 154, unit: 'mm', label: 'A6 ( 216mm x 154mm )'},
				{value: 'square', width: 300, height: 150, unit: 'mm', label: 'Square ( 300mm x 150mm )'},
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
		},
		canvas_width_mm() {
			return this.px_to_mm(this.canvas_width);
		},
		canvas_height_mm() {
			return this.px_to_mm(this.canvas_height);
		},
		canvas_scale_ration() {
			let size = this.card_sizes.find(item => item.value === this.card_size);
			if (typeof size === "object" && size.width) {
				return size.width / this.canvas_width_mm;
			}
			return 1;
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
		mm_to_px(mm) {
			return Math.round(mm * 3.7795275591);
		},
		px_to_mm(px) {
			return Math.round(px * 0.2645833333);
		},
		points_to_mm(points) {
			return Math.round(points * 0.352778);
		},
		addSection(options) {
			if (!options.label.length) {
				options.label = `Section ${this.sections.length + 1}`
			}
			this.sections.push(options);
			this.show_section_modal = false;
		},
		sectionClass(section, index) {
			let classes = [`section-type--${section.section_type}`, `section-index--${index}`]
			return classes
		},
		sectionStyle(section) {
			let styles = [],
				top = this.mm_to_px(section.position.top / this.canvas_scale_ration),
				left = this.mm_to_px(section.position.left / this.canvas_scale_ration);
			styles.push({left: `${left}px`});
			styles.push({top: `${top}px`});
			if (section.section_type === 'static-image' || section.section_type === 'input-image') {
				if (['center', 'right'].indexOf(section.imageOptions.align) !== -1) {
					styles.push({width: '100%', left: '0'})
				}
				if ('center' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'center'})
				}
				if ('right' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'flex-end'})
				}
			}
			if (section.section_type === 'static-text' || section.section_type === 'input-text') {
				let fontSize = this.mm_to_px(this.points_to_mm(section.textOptions.size) / this.canvas_scale_ration);
				styles.push({
					fontFamily: `${section.textOptions.fontFamily}`,
					fontSize: `${fontSize}px`,
					textAlign: `${section.textOptions.align}`,
					color: `${section.textOptions.color}`,
				})
				if (['center', 'right'].indexOf(section.textOptions.align) !== -1) {
					styles.push({width: '100%', left: '0'})
				}
			}
			return styles
		},
		sectionImageStyle(section) {
			let styles = [], width = this.mm_to_px(section.imageOptions.width / this.canvas_scale_ration)
			styles.push({width: `${width}px`});
			return styles;
		},
		calculate_canvas_width() {
			let cardCanvas = this.$el.querySelector('.card-canvas');
			this.canvas_height = cardCanvas.offsetHeight;
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
		this.sections = [
			{
				label: 'Section 1', section_type: 'static-text', position: {top: 10, left: 10},
				text: 'Hello', textOptions: {fontFamily: 'Arial', size: 96, align: 'center', color: '#00ff00'}
			},
			{
				label: 'Section 2', section_type: 'input-text', position: {top: 50, left: 10}, text: '',
				placeholder: 'Jone', textOptions: {fontFamily: 'Arial', size: 80, align: 'center', color: '#323232'}
			},
			{
				label: 'Section 3', section_type: 'static-image', position: {top: 100, left: 10},
				imageOptions: {
					img: {
						id: 37494,
						src: 'https://yousaidit-main.yousaidit.co.uk/bigbasket-logo2.png',
						width: 139,
						height: 88
					},
					width: 101,
					height: 'auto',
					align: 'right'
				}
			},
		];
	}
}
</script>

<style lang="scss">
.card-canvas {
	background-image: url("../img/viewport-bg.png");
	border: 1px dotted rgba(#000, .12);
	display: flex;
	height: 100%;
	position: relative;
	flex-shrink: 0;

	&__background {
		position: absolute;
		width: 100%;
		height: 100%;
	}

	&__section {
		position: absolute;
	}
}
</style>
