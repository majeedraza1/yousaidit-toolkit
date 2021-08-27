<template>
	<div>
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
			<div class="flex h-full relative space-x-4 justify-center" v-show="has_card_size">
				<layer-canvas
					:card_size="card_size"
					:canvas_width="canvas_width"
					:canvas_scale_ration="canvas_scale_ration"
					:image="image"
					:sections="sections"
				/>
				<div class="flex-grow" style="max-width: 300px;">
					<div class="mb-2 flex justify-between items-center">
						<strong v-for="_card_size in card_sizes" v-if="_card_size.value === card_size">
							{{ _card_size.label }}</strong>
						<shapla-button size="small" @click="previewCard">Preview PDF</shapla-button>
					</div>
					<div class="mb-2">
						<h4 class="font-bold mb-2 mt-0 text-base">Background Image</h4>
						<featured-image :image-url="image.src" thumb_size="48px" @click:add="show_image_modal = true"
										@click:clear="image = {}"/>
					</div>
					<div class="mb-2 flex justify-between items-center">
						<h4 class="font-bold mb-0 mt-0 text-base">Sections</h4>
						<svg-icon icon="plus" hoverable title="Add Section" @click.native="show_section_modal = true"/>
					</div>
					<div class="w-full">
						<div v-for="(section, index) in sections" :key="index"
							 class="border border-solid border-gray-400 w-full p-2 rounded mb-2 flex items-center space-x-2">
							<svg-icon icon="sort"/>
							<div class="flex-grow">
								<div class="font-medium">{{ section.label }}</div>
								<div class="text-sm">{{ section.section_type }}</div>
							</div>
							<div>
								<svg-icon icon="pencil" hoverable @click="editSection(section,index)"/>
								<svg-icon icon="delete" hoverable @click="deleteSection(section,index)"/>
							</div>
						</div>
						<shapla-button outline size="small" fullwidth @click.native="show_section_modal = true"
									   class="border-gray-400">
							<svg-icon icon="plus" size="small"/>
							Add Section
						</shapla-button>
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
		</modal>
	</div>
</template>

<script>
import axios from "axios";
import {modal, columns, column, shaplaButton, toggles, toggle, iconContainer} from 'shapla-vue-components'
import {FeaturedImage, MediaModal} from "@/shapla/shapla-media-uploader";
import LayerOptions from "@/components/DynamicCardGenerator/LayerOptions";
import {default as LayerCanvas} from "@/components/DynamicCardGenerator/CardPreview";
import SvgIcon from "@/components/DynamicCardGenerator/SvgIcon";

export default {
	name: "CardCreator",
	components: {
		SvgIcon, LayerCanvas, LayerOptions, modal, columns, column, FeaturedImage, MediaModal, shaplaButton,
		toggles, toggle, iconContainer
	},
	props: {
		active: {type: Boolean, default: false}
	},
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
				{value: 'a4', width: 426, height: 303, unit: 'mm', label: 'A4 ( 213mm x 303mm )'},
				{value: 'a5', width: 303, height: 216, unit: 'mm', label: 'A5 ( 151.5mm x 216mm )'},
				{value: 'a6', width: 216, height: 154, unit: 'mm', label: 'A6 ( 108mm x 154mm )'},
				{value: 'square', width: 300, height: 150, unit: 'mm', label: 'Square ( 150mm x 150mm )'},
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
			this.image = Object.assign({}, image.full || image.thumbnail, {id: image.id});
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
</style>
