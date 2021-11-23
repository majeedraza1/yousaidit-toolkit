<template>
	<modal :active="show_dynamic_card_editor" @close="show_dynamic_card_editor = false" type="box"
		   content-size="full" :show-card-footer="false" class="modal--single-product-dynamic-card"
		   content-class="modal-dynamic-card-content">
		<div class="w-full h-full flex sm:flex-col md:flex-col lg:flex-row lg:space-x-4">
			<div class="flex flex-col flex-grow dynamic-card--canvas">
				<div class="w-full flex dynamic-card--canvas-slider">
					<swiper-slider v-if="show_dynamic_card_editor && Object.keys(payload).length"
								   :card_size="card_size" :slide-to="slideTo" @slideChange="onSlideChange">
						<template v-slot:canvas>
							<card-web-viewer
								:args="payload"
								:upload-url="uploadUrl"
								:images="images"
								:inline-edit="false"
								:active-item-index="activeSectionIndex"
								@edit:section="handleEditSection"
							/>
						</template>
						<template v-slot:inner-message>
							<div class="dynamic-card--editable-content-container">
								<editable-content
									placeholder="Please click here to write your message"
									:font-family="innerMessage.font_family"
									:font-size="innerMessage.font_size"
									:text-align="innerMessage.alignment"
									:color="innerMessage.color"
									v-model="innerMessage.message"
									:card-size="card_size"
								/>
							</div>
						</template>
					</swiper-slider>
				</div>
				<div class="swiper-thumbnail mt-4 dynamic-card--canvas-thumb bg-gray-200">
					<div class="flex space-x-4 p-2 justify-center">
						<image-container container-width="64px" class="bg-gray-100" @click.native="slideTo = 0"
										 :class="{'border border-solid border-primary':slideTo === 0}">
							<img :src="product_thumb" alt="">
						</image-container>
						<image-container container-width="64px" class="bg-gray-100" @click.native="slideTo = 1"
										 :class="{'border border-solid border-primary':slideTo === 1}">
							<img :src="placeholder_im" alt=""/>
						</image-container>
					</div>
				</div>
			</div>
			<div
				class="flex flex-col justify-between bg-gray-100 p-2 dynamic-card--controls lg:border border-solid border-gray-100">
				<div v-if="activeSectionIndex === -1 && slideTo === 0">
					<div><strong>Help tips:</strong></div>
					<div class="flex">
						Click on icon (
						<icon-container size="medium">
							<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
								<rect fill="none" height="24" width="24"></rect>
								<path
									d="M3,10h11v2H3V10z M3,8h11V6H3V8z M3,16h7v-2H3V16z M18.01,12.87l0.71-0.71c0.39-0.39,1.02-0.39,1.41,0l0.71,0.71 c0.39,0.39,0.39,1.02,0,1.41l-0.71,0.71L18.01,12.87z M17.3,13.58l-5.3,5.3V21h2.12l5.3-5.3L17.3,13.58z"></path>
							</svg>
						</icon-container>
						) to customize text.
					</div>
					<div class="flex">
						Click on icon (
						<icon-container size="medium">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px" height="24px">
								<rect fill="none" height="24" width="24"></rect>
								<path
									d="M18.85,10.39l1.06-1.06c0.78-0.78,0.78-2.05,0-2.83L18.5,5.09c-0.78-0.78-2.05-0.78-2.83,0l-1.06,1.06L18.85,10.39z M14.61,11.81L7.41,19H6v-1.41l7.19-7.19L14.61,11.81z M13.19,7.56L4,16.76V21h4.24l9.19-9.19L13.19,7.56L13.19,7.56z M19,17.5 c0,2.19-2.54,3.5-5,3.5c-0.55,0-1-0.45-1-1s0.45-1,1-1c1.54,0,3-0.73,3-1.5c0-0.47-0.48-0.87-1.23-1.2l1.48-1.48 C18.32,15.45,19,16.29,19,17.5z M4.58,13.35C3.61,12.79,3,12.06,3,11c0-1.8,1.89-2.63,3.56-3.36C7.59,7.18,9,6.56,9,6 c0-0.41-0.78-1-2-1C5.74,5,5.2,5.61,5.17,5.64C4.82,6.05,4.19,6.1,3.77,5.76C3.36,5.42,3.28,4.81,3.62,4.38C3.73,4.24,4.76,3,7,3 c2.24,0,4,1.32,4,3c0,1.87-1.93,2.72-3.64,3.47C6.42,9.88,5,10.5,5,11c0,0.31,0.43,0.6,1.07,0.86L4.58,13.35z"></path>
							</svg>
						</icon-container>
						) to customize image.
					</div>
				</div>
				<template v-if="activeSectionIndex >= 0">
					<div v-if="activeSection.section_type === 'input-text'">
						<input type="text" v-model="activeSection.text" :placeholder="activeSection.placeholder">
						<shapla-button outline size="small" @click="activeSection.text = ''">Clear</shapla-button>
						<shapla-button outline size="small" theme="primary" @click="closeSection">Confirm
						</shapla-button>
					</div>
					<div v-if="activeSection.section_type === 'input-image'">
						<div v-if="!isUserLoggedIn"
							 class="border border-dotted border-primary text-primary font-bold mb-4 p-2 text-center">
							Log-in to save image for later use.
						</div>
						<file-uploader
							:url="uploadUrl"
							@success="finishedEvent"
							@before:send="beforeSendEvent"
						/>
						<div>{{ images }}</div>
						<div class="relative border border-solid mt-6"
							 v-if="activeSection.image && activeSection.image.src">
							<img :src="activeSection.image.src" alt=""/>
							<delete-icon class="absolute -top-2 -right-2" @click="removeImage"/>
						</div>
					</div>
				</template>
				<div v-if="slideTo !== 0">
					<editor-controls v-model="innerMessage" @change="onChangeEditorControls"/>
				</div>
				<div>
					<shapla-button theme="primary" size="medium" fullwidth @click="handleSubmit">Add to basket and
						continue shopping
					</shapla-button>
				</div>
			</div>
		</div>
	</modal>
</template>

<script>
import axios from "axios";
import {modal, shaplaButton, iconContainer, imageContainer, FileUploader} from "shapla-vue-components";
import CardWebViewer from "@/components/DynamicCardPreview/CardWebViewer";
import SwiperSlider from './SwiperSlider';
import EditableContent from "@/frontend/inner-message/EditableContent";
import EditorControls from "@/frontend/inner-message/EditorControls";

export default {
	name: "SingleProductDynamicCard",
	components: {
		EditableContent, CardWebViewer, modal, shaplaButton, iconContainer, SwiperSlider, imageContainer,
		EditorControls, FileUploader
	},
	data() {
		return {
			loading: false,
			slideTo: 0,
			product_id: 0,
			card_size: '',
			show_dynamic_card_editor: true,
			payload: {},
			innerMessage: {
				message: '',
				font_family: "'Indie Flower', cursive",
				font_size: '18',
				alignment: 'center',
				color: '#1D1D1B',
			},
			readFromServer: false,
			images: [],
			activeSection: {},
			activeSectionIndex: -1,
			product_thumb: '',
			placeholder_im: '',
		}
	},
	computed: {
		uploadUrl() {
			return StackonetToolkit.restRoot + '/dynamic-cards/media';
		},
		isUserLoggedIn() {
			return window.StackonetToolkit.isUserLoggedIn || false;
		}
	},
	watch: {
		slideTo() {
			this.closeSection();
		}
	},
	methods: {
		closeSection() {
			this.activeSection = {};
			this.activeSectionIndex = -1;
		},
		removeImage() {
			if (this.activeSection.image) {
				delete this.activeSection.image;
			}
			this.closeSection();
		},
		onChangeEditorControls(args) {
			if ('emoji' === args.key) {
				document.execCommand("insertHtml", false, args.payload);
			}
		},
		onSlideChange(activeIndex) {
			if (activeIndex !== this.slideTo) {
				this.slideTo = activeIndex;
			}
		},
		handleEditSection(section, index) {
			this.activeSectionIndex = index;
			this.activeSection = section;
		},
		handleSubmit() {
			let fieldsContainer = document.querySelector('#_dynamic_card_fields');
			this.payload.card_items.forEach((item, index) => {
				let inputId = `#_dynamic_card_input-${index}`
				if (['static-text', 'input-text'].indexOf(item.section_type) !== -1) {
					fieldsContainer.querySelector(inputId).value = item.text;
				}
				if (['static-image', 'input-image'].indexOf(item.section_type) !== -1) {
					fieldsContainer.querySelector(inputId).value = item.imageOptions.img.id;
				}
			});
			let imContainer = document.querySelector('#_inner_message_fields');
			if (imContainer) {
				imContainer.querySelector('#_inner_message_content').value = this.innerMessage.message;
				imContainer.querySelector('#_inner_message_font').value = this.innerMessage.font_family;
				imContainer.querySelector('#_inner_message_size').value = this.innerMessage.font_size;
				imContainer.querySelector('#_inner_message_align').value = this.innerMessage.alignment;
				imContainer.querySelector('#_inner_message_color').value = this.innerMessage.color;
			}
			let variations_form = document.querySelector('form.cart');
			if (variations_form) {
				this.loading = true;
				variations_form.submit();
			}
		},
		loadCardInfo() {
			if (this.readFromServer) {
				return;
			}
			axios.get(StackonetToolkit.restRoot + `/dynamic-cards/${this.product_id}`).then(response => {
				let data = response.data.data;
				this.payload = data.payload;
				this.product_thumb = data.product_thumb;
				this.placeholder_im = data.placeholder_im;
				this.readFromServer = true;
			});
		},
		beforeSendEvent(xhr) {
			if (window.StackonetToolkit.restNonce) {
				xhr.setRequestHeader('X-WP-Nonce', window.StackonetToolkit.restNonce);
			}
		},
		finishedEvent(fileObject, response) {
			this.$emit('success', fileObject, response);
		},
		fetchImages() {
			axios.get(this.uploadUrl).then(response => {
				if (response.data.data) {
					this.images = response.data.data;
				}
			})
		}
	},
	mounted() {
		let el = document.querySelector('#dynamic-card-container');
		if (el) {
			this.product_id = parseInt(el.dataset.productId);
			this.card_size = el.dataset.cardSize;
		}

		this.loadCardInfo();
		this.fetchImages();

		let btn = document.querySelector('.button--customize-dynamic-card');
		if (btn) {
			if (btn.hasAttribute('disabled')) {
				btn.removeAttribute('disabled');
			}
			btn.addEventListener('click', event => {
				event.preventDefault();
				this.show_dynamic_card_editor = true;
			});
		}
	}
}
</script>

<style lang="scss">
.dynamic-card--editable-content-container {
	display: flex;
	height: 100%;
	justify-content: center;
	align-items: center;
	border: 1px solid #f5f5f5;
}

.modal--single-product-dynamic-card {
	box-sizing: border-box;

	*, *:before, *:after {
		box-sizing: border-box;
	}

	.card-preview-canvas {
		border: 1px solid #f5f5f5;
	}

	.modal-dynamic-card-content {
		border-radius: 0;
		height: 100vh;
		max-height: 100vh;
		width: 100vw;

		.admin-bar & {
			margin-top: 32px;
			height: calc(100vh - 32px);

			@media screen and (max-width: 782px) {
				margin-top: 46px;
				height: calc(100vh - 46px);
			}
		}
	}

	@media screen and (min-width: 1024px) {
		.modal-dynamic-card-content {
			overflow: hidden;
		}
		.dynamic-card--canvas {
			height: calc(100vh - 2rem); // excluding padding of modal box
			width: calc(100% - 320px);

			&-slider {
				height: calc(100vh - (2rem + 100px + 1rem)); // excluding padding of modal box
			}

			&-thumb {
				height: 100px;
			}
		}

		.dynamic-card--controls {
			width: 320px;
		}
	}
}
</style>
