<template>
	<div :class="`card-preview-canvas card-canvas--${options.card_size}`" :style="canvas_styles">
		<div class="card-canvas__background is-type-color" v-if="options.card_bg_type === 'color'"
			 :style="`background-color:${options.card_bg_color}`"></div>
		<img class="card-canvas__background" v-if="Object.keys(options.card_background).length"
			 :src="options.card_background.src" alt="">

		<div v-for="(section,index) in options.card_items"
			 class="card-preview-canvas__section"
			 :class="sectionClass(section,index)"
			 :style="sectionStyle(section)"
		>
			<template v-if="section.section_type === 'static-text'">
				{{ section.text }}
			</template>
			<div v-if="section.section_type === 'input-text'" class="card-preview-canvas__section-edit is-text-edit">
				<div class="card-preview-canvas__section-edit-icon" @click="onClickEditSection(section,index)">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"
						 data-tooltip-target="show_section_edit_popover">
						<rect fill="none" height="24" width="24"/>
						<path
							d="M3,10h11v2H3V10z M3,8h11V6H3V8z M3,16h7v-2H3V16z M18.01,12.87l0.71-0.71c0.39-0.39,1.02-0.39,1.41,0l0.71,0.71 c0.39,0.39,0.39,1.02,0,1.41l-0.71,0.71L18.01,12.87z M17.3,13.58l-5.3,5.3V21h2.12l5.3-5.3L17.3,13.58z"/>
					</svg>
				</div>
				{{ section.text ? section.text : section.placeholder }}
			</div>
			<template v-if="section.section_type === 'static-image'">
				<img :src="section.imageOptions.img.src" alt="" :style="sectionImageStyle(section)">
			</template>
			<div v-if="section.section_type === 'input-image'" class="card-preview-canvas__section-edit is-image-edit">
				<div class="card-preview-canvas__section-edit-icon" @click="onClickEditSection(section,index)">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px" height="24px"
						 data-tooltip-target="show_section_edit_popover">
						<rect fill="none" height="24" width="24"/>
						<path
							d="M18.85,10.39l1.06-1.06c0.78-0.78,0.78-2.05,0-2.83L18.5,5.09c-0.78-0.78-2.05-0.78-2.83,0l-1.06,1.06L18.85,10.39z M14.61,11.81L7.41,19H6v-1.41l7.19-7.19L14.61,11.81z M13.19,7.56L4,16.76V21h4.24l9.19-9.19L13.19,7.56L13.19,7.56z M19,17.5 c0,2.19-2.54,3.5-5,3.5c-0.55,0-1-0.45-1-1s0.45-1,1-1c1.54,0,3-0.73,3-1.5c0-0.47-0.48-0.87-1.23-1.2l1.48-1.48 C18.32,15.45,19,16.29,19,17.5z M4.58,13.35C3.61,12.79,3,12.06,3,11c0-1.8,1.89-2.63,3.56-3.36C7.59,7.18,9,6.56,9,6 c0-0.41-0.78-1-2-1C5.74,5,5.2,5.61,5.17,5.64C4.82,6.05,4.19,6.1,3.77,5.76C3.36,5.42,3.28,4.81,3.62,4.38C3.73,4.24,4.76,3,7,3 c2.24,0,4,1.32,4,3c0,1.87-1.93,2.72-3.64,3.47C6.42,9.88,5,10.5,5,11c0,0.31,0.43,0.6,1.07,0.86L4.58,13.35z"/>
					</svg>
				</div>
				<img v-if="section.image && section.image.src" :src="section.image.src" alt=""
					 :style="sectionImageStyle(section)">
				<img v-else :src="section.imageOptions.img.src" alt="" :style="sectionImageStyle(section)">
			</div>
		</div>
		<div class="shapla-popover" id="show_section_edit_popover" data-popover-for="show_section_edit_popover">
			<div class="shapla-popover__arrow"></div>
			<div class="shapla-popover__header flex justify-between items-center">
				{{ 'input-text' === activeSection.section_type ? 'Edit Text' : 'Edit Image' }}
				<icon-container hoverable size="medium" @click="closePopover">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
						<path d="M0 0h24v24H0V0z" fill="none"/>
						<path
							d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
					</svg>
				</icon-container>
			</div>
			<div class="shapla-popover__body">
				<template v-if="'input-text' === activeSection.section_type">
					<input type="text" v-model="activeSection.text" :placeholder="activeSection.placeholder">
					<shapla-button outline size="small" @click="activeSection.text = ''">Clear</shapla-button>
					<shapla-button outline size="small" theme="primary" @click="closePopover">Confirm</shapla-button>
				</template>
				<template v-if="'input-image' === activeSection.section_type">
					<shapla-button theme="primary" outline size="small" fullwidth @click="showMediaModal = true">
						Add image
					</shapla-button>
					<div class="relative border border-solid mt-6"
						 v-if="activeSection.image && activeSection.image.src">
						<img :src="activeSection.image.src" alt=""/>
						<delete-icon class="absolute -top-2 -right-2" @click="removeImage"/>
					</div>
				</template>
			</div>
		</div>

		<media-modal
			:active="showMediaModal"
			@close="showMediaModal = false"
			:url="uploadUrl"
			:images="images"
			@success="(fileObject, response)=>$emit('success', fileObject, response)"
			@before:send="addToken"
			@select:image="handleImageSelect"
		/>
	</div>
</template>

<script>
import Popover from '@/shapla/popover';
import {shaplaButton, iconContainer, FileUploader, deleteIcon} from "shapla-vue-components";
import {FeaturedImage, MediaModal} from "@/shapla/shapla-media-uploader";

const optionArgs = {card_size: '', card_bg_type: '', card_bg_color: '', card_background: {}, card_items: []};
export default {
	name: "CardWebViewer",
	components: {shaplaButton, iconContainer, FeaturedImage, FileUploader, MediaModal, deleteIcon},
	props: {
		args: {
			type: Object,
			default: () => {
				return optionArgs
			}
		},
		card_size: {type: String, default: ''},
		uploadUrl: {type: String, default: ''},
		images: {type: Array, default: () => []}
	},
	data() {
		return {
			showMediaModal: false,
			options: optionArgs,
			card_sizes: {
				a4: [426, 303],
				a5: [303, 216],
				a6: [216, 154],
				square: [300, 150],
			},
			canvas_width: 0,
			canvas_height: 0,
			activeSection: {},
			activeSectionIndex: -1,
		}
	},
	watch: {
		args(newValue) {
			this.options = newValue;
		}
	},
	computed: {
		font_families() {
			return window.StackonetToolkit.fonts || window.DesignerProfile.fonts;
		},
		_card_size() {
			return this.card_size || this.options.card_size;
		},
		card_dimension() {
			if (Object.keys(this.card_sizes).indexOf(this.options.card_size) === -1) {
				return [0, 0];
			}
			let dimension = this.card_sizes[this.options.card_size];
			return [dimension[0] / 2, dimension[1]];
		},
		canvas_width_mm() {
			return this.px_to_mm(this.canvas_width);
		},
		card_height_in_mm() {
			return this.px_to_mm(this.canvas_height);
		},
		canvas_scale_ration() {
			return this.card_dimension[0] / this.px_to_mm(this.canvas_width);
		},
		canvas_styles() {
			return {
				width: `${this.canvas_width}px`
			}
		}
	},
	methods: {
		/**
		 * @param {XMLHttpRequest} xhr
		 */
		addToken(xhr) {
			xhr.setRequestHeader('X-WP-Nonce', window.Stackonet.nonce);
		},
		handleImageSelect(image) {
			this.activeSection.image = {id: image.id, ...image.full}
		},
		mm_to_px(mm) {
			return Math.round(mm * 3.7795275591);
		},
		px_to_mm(px) {
			return Math.round(px * 0.2645833333);
		},
		points_to_mm(points) {
			return Math.round(points * 0.352778);
		},
		calculate_canvas_dimension() {
			let cardCanvas = this.$el;
			let card_dimension = this.card_dimension;
			this.canvas_height = cardCanvas.offsetHeight;
			this.canvas_width = Math.round(card_dimension[0] / card_dimension[1] * cardCanvas.offsetHeight);
		},
		sectionClass(section, index) {
			return [`section-type--${section.section_type}`, `section-index--${index}`]
		},
		sectionStyle(section) {
			let styles = [],
				_top = Math.round(100 / this.card_height_in_mm * section.position.top),
				_left = Math.round(100 / this.card_width_in_mm * section.position.left);

			styles.push({left: `${_left}%`});
			styles.push({top: `${_top}%`});

			if (section.section_type === 'static-image' || section.section_type === 'input-image') {
				if (['center', 'right'].indexOf(section.imageOptions.align) !== -1) {
					styles.push({width: '100%', left: '0%'})
				}
				if ('center' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'center'})
				}
				if ('right' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'flex-end'})
				}
			}
			if (section.section_type === 'static-text' || section.section_type === 'input-text') {
				let fontSize = Math.round((section.textOptions.size / this.canvas_scale_ration)),
					fontFamily = this.font_families.find(_font => _font.key === section.textOptions.fontFamily);
				styles.push({
					fontFamily: `"${fontFamily.label}"`,
					fontSize: `${fontSize}pt`,
					textAlign: `${section.textOptions.align}`,
					color: `${section.textOptions.color}`,
				})
				if (['center', 'right'].indexOf(section.textOptions.align) !== -1) {
					styles.push({width: '100%', left: '0%'})
				}
			}
			return styles
		},
		sectionImageStyle(section) {
			let styles = [], width = Math.round(100 / this.card_dimension[0] * section.imageOptions.width);
			styles.push({width: `${width}%`});
			return styles;
		},
		onClickEditSection(section, index) {
			if (section.section_type === 'input-image' && !section.image) {
				section.image = {}
			}
			this.activeSection = section;
			this.activeSectionIndex = index;
		},
		removeImage() {
			if (this.activeSection.image) {
				delete this.activeSection.image;
			}
			this.closePopover();
		},
		closePopover() {
			let popover = this.$el.querySelector('#show_section_edit_popover');
			if (popover.classList.contains('is-active')) {
				popover.classList.remove('is-active');
			}
			this.activeSection = {}
		}
	},
	mounted() {
		this.options = this.args;
		setTimeout(() => {
			this.calculate_canvas_dimension()

			let editElements = this.$el.querySelectorAll(".card-preview-canvas__section-edit-icon");
			editElements.forEach(el => new Popover(el.querySelector('svg'), {
				hideEvents: [],
			}));
		}, 100);
	}
}
</script>

<style lang="scss">
.card-preview-canvas {
	background-color: white;
	border: 1px dotted rgba(#000, .12);
	display: flex;
	height: 100%;
	position: relative;
	flex-shrink: 0;
	overflow: hidden;

	&__background {
		position: absolute;
		width: 100%;
		height: 100%;
	}

	&__section {
		position: absolute;
		line-height: 1;
	}

	&__section-edit {
		border: 1px dotted rgba(#000, 0.12);
		position: relative;
		transition: 300ms all ease-in-out;

		&:hover {
			background-color: var(--shapla-primary-alpha);
		}
	}

	&__section-edit-icon {
		background-color: white;
		position: absolute;
		top: 0;
		left: 0;
		width: 32px;
		height: 32px;
		overflow: hidden;
		display: flex;
		justify-content: center;
		align-items: center;
		border: 1px solid var(--shapla-primary);
		cursor: pointer;
		transition: 300ms all ease-in-out;

		&:hover {
			border-radius: 16px;
		}

		svg {
			display: block;
			fill: currentColor;
		}
	}
}
</style>
