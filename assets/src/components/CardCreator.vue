<template>
	<modal :active="active" title="Add Dynamic Card" content-size="full" @close="$emit('close')">
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
				<div class="mb-2">
					<shapla-button theme="primary" fullwidth @click="previewCard">Preview PDF</shapla-button>
				</div>
				<div class="mb-2">
					<template v-for="_card_size in card_sizes" v-if="_card_size.value === card_size">
						<strong>{{ _card_size.label }}</strong>
					</template>
				</div>
				<div class="mb-2">
					<h4 class="font-bold mb-2 mt-0 text-base">Background Image</h4>
					<featured-image @click:add="show_image_modal = true"/>
				</div>
				<div>
					<h4 class="font-bold mb-2 mt-0 text-base">Section</h4>
					<p>
						<shapla-button @click.native="show_section_modal = true">Add section</shapla-button>
					</p>
				</div>
			</div>
			<div class="flex-grow">
				<h4>Sections</h4>
				<div class="w-full">
					<div v-for="(section, index) in sections" :key="index"
						 class="border border-solid border-gray-400 w-full p-2 rounded mb-2 flex items-center space-x-2">
						<icon-container hoverable>
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"
								 fill="#000000">
								<path d="M0 0h24v24H0V0z" fill="none"/>
								<path
									d="M11 18c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm-2-8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm6 4c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
							</svg>
						</icon-container>
						<div class="flex-grow">
							<div class="font-medium">{{ section.label }}</div>
							<div class="text-sm">{{ section.section_type }}</div>
						</div>
						<div>
							<icon-container hoverable @click="editSection(section,index)">
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"
									 fill="#000000">
									<path d="M0 0h24v24H0V0z" fill="none"/>
									<path
										d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
								</svg>
							</icon-container>
							<icon-container hoverable @click="deleteSection(section,index)">
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"
									 fill="#000000">
									<path d="M0 0h24v24H0V0z" fill="none"/>
									<path
										d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-3.5l-1-1zM18 7H6v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7z"/>
								</svg>
							</icon-container>
						</div>
					</div>
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
			<layer-options
				:active="show_section_modal"
				@submit="addSection"
				@upload="refreshMediaList"
				@cancel="show_section_modal = false"
				:images="images"
			/>
			<layer-options
				:active="show_section_edit_modal"
				:title="`Edit Section: ${active_section.label}`"
				mode="edit"
				@submit="updateSection"
				@upload="refreshMediaList"
				@cancel="show_section_edit_modal = false"
				:images="images"
				:value="active_section"
			/>
		</div>
	</modal>
</template>

<script>
import {modal, columns, column, shaplaButton, toggles, toggle, iconContainer} from 'shapla-vue-components'
import {FeaturedImage, MediaModal} from "@/shapla/shapla-media-uploader";
import axios from "axios";
import LayerOptions from "@/components/LayerOptions";

export default {
	name: "CardCreator",
	components: {
		LayerOptions, modal, columns, column, FeaturedImage, MediaModal, shaplaButton, toggles, toggle,
		iconContainer
	},
	props: {
		active: {type: Boolean, default: false}
	},
	// emits: ['close'],
	data() {
		return {
			canvas_width: 0,
			canvas_height: 0,
			show_image_modal: false,
			show_section_modal: false,
			show_section_edit_modal: false,
			active_section_index: -1,
			active_section: {},
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
		editSection(section, index) {
			this.active_section = section;
			this.active_section_index = index;
			this.show_section_edit_modal = true;
		},
		updateSection(sectionData) {
			this.sections[this.active_section_index] = sectionData;
			this.show_section_edit_modal = false;
			this.active_section = {};
		},
		deleteSection(section, index) {
			this.$dialog.confirm('Are you sure to delete the section?').then(confirmed => {
				if (confirmed) {
					this.sections.splice(index, 1);
				}
			})
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
				return Math.round((300 / 2) / 150 * cardCanvas.offsetHeight);
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
		previewCard() {
			this.$store.commit('SET_LOADING_STATUS', true);
			let data = new FormData();
			data.append('action', 'yousaidit_generate_preview_card');
			data.append('card_size', this.card_size);
			data.append('card_background', JSON.stringify(this.image));
			data.append('card_items', JSON.stringify(this.sections));
			axios.post(window.StackonetToolkit.ajaxUrl, data).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				window.open(response.data.data.redirect, '_blank');
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			});
		},
		setTextData() {
			this.card_size = 'square';
			this.canvas_width = this.calculate_canvas_width();
			this.sections = [
				{
					label: 'Section 1',
					section_type: 'static-text',
					position: {top: 30, left: 10},
					text: 'Hello',
					textOptions: {fontFamily: 'Indie Flower', size: 96, align: 'center', color: '#00ff00'}
				},
				{
					label: 'Section 2',
					section_type: 'input-text',
					position: {top: 50, left: 10},
					text: '',
					placeholder: 'Jone',
					textOptions: {fontFamily: 'Indie Flower', size: 80, align: 'center', color: '#323232'}
				},
				{
					label: 'Section 3', section_type: 'static-image', position: {top: 100, left: 10},
					imageOptions: {
						img: {
							id: 808,
							src: 'http://yousaidit.test/wp-content/uploads/2017/02/YouSaidIt_logo.png',
							width: 1030,
							height: 428
						},
						width: 101,
						height: 'auto',
						align: 'right'
					}
				},
			];
		}
	},
	mounted() {
		this.getUserUploadedImages();
		// Test data
		this.setTextData();
	}
}
</script>

<style lang="scss">
@import url('https://fonts.googleapis.com/css2?family=Indie+Flower&display=swap');

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
		line-height: 1;
	}
}
</style>
