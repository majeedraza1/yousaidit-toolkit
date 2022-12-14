<template>
	<div class="multi-compose w-full h-full flex sm:flex-col md:flex-col lg:flex-row lg:space-x-4">
		<div class="flex flex-col flex-grow dynamic-card--canvas">
			<div class="w-full h-full flex dynamic-card--canvas-slider">
				<swiper-slider :card_size="cardSize" :slide-to="slideTo" :hide-canvas="true"
							   @slideChange="onSlideChange">
					<template v-slot:video-message>
						<div class="dynamic-card--editable-content-container">
							<editable-content
								placeholder="Please click here to write your message"
								:font-family="leftInnerMessage.font"
								:font-size="leftInnerMessage.size"
								:text-align="leftInnerMessage.alignment"
								:color="leftInnerMessage.color"
								v-model="leftInnerMessage.content"
								:card-size="cardSize"
								@lengthError="error => onLengthError(error, 'left')"
							/>
							<div v-if="leftInnerMessage.showLengthError" class="has-error p-2 my-4 absolute bottom-0">
								Oops... your message is too long, please keep inside the box.
							</div>
						</div>
					</template>
					<template v-slot:inner-message>
						<div class="dynamic-card--editable-content-container">
							<editable-content
								placeholder="Please click here to write your message"
								:font-family="rightInnerMessage.font"
								:font-size="rightInnerMessage.size"
								:text-align="rightInnerMessage.alignment"
								:color="rightInnerMessage.color"
								v-model="rightInnerMessage.content"
								:card-size="cardSize"
								@lengthError="error => onLengthError(error, 'right')"
							/>
							<div v-if="rightInnerMessage.showLengthError" class="has-error p-2 my-4 absolute bottom-0">
								Oops... your message is too long, please keep inside the box.
							</div>
						</div>
					</template>
				</swiper-slider>
			</div>
			<div class="swiper-thumbnail mt-4 dynamic-card--canvas-thumb bg-gray-200">
				<div class="flex space-x-4 p-2 justify-center">
					<image-container container-width="64px" class="bg-gray-100" @click.native="slideTo = 0"
									 :class="{'border border-solid border-primary':slideTo === 0}">
						<img :src="placeholder_im" alt=""/>
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
			<div v-if="slideTo === 0">
				<editor-controls v-model="leftInnerMessage" @change="onChangeEditorControls"/>
			</div>
			<div v-if="slideTo === 1">
				<editor-controls v-model="rightInnerMessage" @change="onChangeEditorControls"/>
			</div>
			<div class="space-y-2">
				<shapla-button theme="primary" size="small" fullwidth outline @click="$emit('close')">
					Cancel
				</shapla-button>
				<shapla-button theme="primary" size="medium" fullwidth @click="handleSubmit">
					Add to basket
				</shapla-button>
			</div>
		</div>
	</div>
</template>

<script>
import {
	ConfirmDialog,
	deleteIcon,
	FileUploader,
	iconContainer,
	imageContainer,
	modal,
	notification,
	shaplaButton,
	tab,
	tabs
} from "shapla-vue-components";
import CardWebViewer from "@/components/DynamicCardPreview/CardWebViewer";
import EditableContent from "@/frontend/inner-message/EditableContent";
import EditorControls from "@/frontend/inner-message/EditorControls";
import SwiperSlider from "@/frontend/dynamic-card/SwiperSlider.vue";
import VideoInnerMessage from "@/frontend/dynamic-card/VideoInnerMessage";

const defaults = () => {
	return {
		alignment: 'center',
		color: '#1D1D1B',
		content: '',
		font: "'Indie Flower', cursive",
		size: '18',
		showLengthError: false,
	}
}

export default {
	name: "MultiCompose",
	components: {
		EditableContent, CardWebViewer, modal, shaplaButton, iconContainer, SwiperSlider, imageContainer,
		VideoInnerMessage, EditorControls, FileUploader, tabs, tab, deleteIcon, notification, ConfirmDialog
	},
	props: {
		active: {type: Boolean, default: false},
		cardSize: {type: String},
		leftMessage: {type: Object, default: () => defaults()},
		rightMessage: {type: Object, default: () => defaults()},
		btnText: {type: String, default: 'Add to Basket'}
	},
	data() {
		return {
			slideTo: 0,
			leftInnerMessage: defaults(),
			rightInnerMessage: defaults(),
		}
	},
	computed: {
		placeholder_im() {
			return window.StackonetToolkit.placeholderUrlIM;
		}
	},
	watch: {
		leftMessage: {
			deep: true,
			handler(newValue) {
				this.leftInnerMessage.message = newValue.content;
				this.leftInnerMessage.font_family = newValue.font;
				this.leftInnerMessage.font_size = newValue.size;
				this.leftInnerMessage.alignment = newValue.align;
				this.leftInnerMessage.color = newValue.color;
			}
		},
		rightMessage: {
			deep: true,
			handler(newValue) {
				this.rightInnerMessage.message = newValue.content;
				this.rightInnerMessage.font_family = newValue.font;
				this.rightInnerMessage.font_size = newValue.size;
				this.rightInnerMessage.alignment = newValue.align;
				this.rightInnerMessage.color = newValue.color;
			}
		},
	},
	methods: {
		onSlideChange(activeIndex) {
			if (activeIndex !== this.slideTo) {
				this.slideTo = activeIndex;
			}
		},
		onLengthError(error, side = null) {
			if (side === 'left') {
				this.leftInnerMessage.showLengthError = error;
			} else if (side === 'right') {
				this.rightInnerMessage.showLengthError = error;
			}
		},
		onChangeEditorControls(args) {
			if ('emoji' === args.key) {
				document.execCommand("insertHtml", false, args.payload);
			}
		},
		handleSubmit() {
			this.$emit('submit', {
				left: this.leftInnerMessage,
				right: this.rightInnerMessage
			});
		}
	},
	mounted() {
		this.leftInnerMessage = this.leftMessage;
		this.rightInnerMessage = this.rightMessage;
	}
}
</script>

<style lang="scss">
.multi-compose {
	&, *:before, *:after {
		box-sizing: border-box;
	}
}

.has-multi-compose {
	&, *:before, *:after {
		box-sizing: border-box;
	}

	.shapla-modal-content {
		overflow: hidden;
	}
}
</style>
