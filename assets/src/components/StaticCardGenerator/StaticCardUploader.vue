<template>
	<div class="flex -m-2">
		<div class="sm:w-full md:w-1/2 p-2">
			<image-container v-if="!hasImage">
				<file-uploader
					class="static-card-image-uploader"
					:url="attachment_upload_url"
					@before:send="handleBeforeSend"
					@success="handleImageUpload"
					@failed="handleImageUploadFailed"
				/>
			</image-container>
			<image-container v-if="hasImage">
				<img :src="previewImage.src"/>
			</image-container>
			<div class="flex justify-center mt-4">
				<shapla-button v-if="hasImage" theme="primary" size="small" @click="removeImage">Remove Image
				</shapla-button>
			</div>
		</div>
		<div class="sm:w-full md:w-1/2 p-2">
			<div>
				<h2 class="text-2xl leading-none mb-4">Card Size</h2>
				<p>The size we're printing is square (15cm x 15cm), please upload the image in JPEG or PNG format with a
					minimum resolution of 1807 x 1807 px.</p>
			</div>
			<div>
				<h2 class="text-2xl leading-none mb-4">Bleed Needed</h2>
				<p>For the best results, please ensure your design as a 3mm bleed on the top, right and bottom and 1mm
					on the left of your design. These parts will get cut off when printed, anything you would like on
					the printed design must be kept within the cropping masks.</p>
			</div>
			<div>
				<h2 class="text-2xl leading-none mb-4">Templates</h2>
				<p>To make it easier, why not download one of our templates to ensure your artwork is going to be print
					ready.</p>
				<icon-container size="large" @click="downloadTemplate('ps')">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
						<path fill="#00c8ff"
						      d="M0 0.4v31.2h32v-31.2zM1.333 1.733h29.333v28.533h-29.333zM7.733 7.707c0-0.089 0.187-0.155 0.299-0.155 0.859-0.044 2.117-0.067 3.437-0.067 3.696 0 5.133 2.027 5.133 4.621 0 3.387-2.456 4.84-5.469 4.84-0.507 0-0.68-0.023-1.033-0.023v5.123c0 0.111-0.044 0.155-0.153 0.155h-2.059c-0.111 0-0.153-0.040-0.153-0.151zM10.1 14.789c0.307 0.021 0.549 0.021 1.080 0.021 1.56 0 3.027-0.549 3.027-2.661 0-1.693-1.048-2.552-2.829-2.552-0.528 0-1.033 0.021-1.276 0.044zM21.576 13.205c-1.056 0-1.408 0.528-1.408 0.968 0 0.484 0.24 0.813 1.649 1.54 2.091 1.013 2.749 1.98 2.749 3.409 0 2.133-1.627 3.28-3.827 3.28-1.168 0-2.16-0.244-2.733-0.573-0.087-0.044-0.107-0.109-0.107-0.22v-1.956c0-0.133 0.064-0.177 0.152-0.112 0.832 0.551 1.803 0.792 2.683 0.792 1.056 0 1.496-0.44 1.496-1.035 0-0.484-0.307-0.903-1.649-1.607-1.893-0.907-2.685-1.827-2.685-3.369 0-1.716 1.341-3.147 3.673-3.147 1.147 0 1.952 0.176 2.392 0.373 0.109 0.067 0.133 0.176 0.133 0.264v1.827c0 0.111-0.067 0.177-0.2 0.133-0.592-0.352-1.467-0.573-2.319-0.568z"></path>
					</svg>
				</icon-container>
				<icon-container size="large" @click="downloadTemplate('ai')">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
						<path fill="#ff7c00"
						      d="M0 0.4v31.2h32v-31.2zM1.333 1.733h29.333v28.533h-29.333zM11.1 18.067l-1.056 3.997c-0.023 0.111-0.067 0.136-0.197 0.136h-1.957c-0.133 0-0.153-0.044-0.133-0.197l3.787-13.26c0.067-0.24 0.109-0.451 0.131-1.111 0-0.088 0.044-0.133 0.111-0.133h2.795c0.088 0 0.133 0.024 0.155 0.133l4.247 14.392c0.023 0.111 0 0.176-0.111 0.176h-2.2c-0.111 0-0.176-0.027-0.197-0.115l-1.1-4.020zM14.817 15.9c-0.373-1.475-1.253-4.704-1.584-6.267h-0.023c-0.285 1.56-0.989 4.2-1.54 6.267zM20.817 8.489c0-0.857 0.593-1.364 1.364-1.364 0.813 0 1.364 0.549 1.364 1.364 0 0.88-0.573 1.364-1.387 1.364-0.8 0-1.347-0.484-1.341-1.364zM20.967 11.521c0-0.107 0.044-0.147 0.155-0.147h2.093c0.117 0 0.16 0.044 0.16 0.155v10.527c0 0.111-0.021 0.155-0.153 0.155h-2.067c-0.133 0-0.177-0.067-0.177-0.173z"></path>
					</svg>
				</icon-container>
			</div>
		</div>
	</div>
</template>

<script>
import {FileUploader, imageContainer, iconContainer, shaplaButton} from "shapla-vue-components";

export default {
	name: "StaticCardUploader",
	components: {FileUploader, imageContainer, iconContainer, shaplaButton},
	props: {
		cardSize: {type: String, default: 'square'},
		image: {type: Object, default: () => ({})}
	},
	computed: {
		designer_id() {
			return DesignerProfile.user.id;
		},
		attachment_upload_url() {
			return DesignerProfile.restRoot + '/designers/' + this.designer_id + '/attachment';
		},
		hasImage() {
			return Object.keys(this.image).length > 0;
		},
		previewImage() {
			if (!this.hasImage) {
				return {}
			}
			return this.image.full || this.image.thumbnail;
		}
	},
	methods: {
		/**
		 * @param {XMLHttpRequest} xhr
		 * @param {FormData} formData
		 */
		handleBeforeSend(xhr, formData) {
			xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
			formData.append('type', 'card_image');
			formData.append('card_size', this.cardSize);
		},
		handleImageUpload(fileObject, serverResponse) {
			this.$emit('upload', fileObject, serverResponse)
		},
		handleImageUploadFailed(fileObject, serverResponse) {
			this.$emit('failed', fileObject, serverResponse)
		},
		downloadTemplate(templateName) {
			this.$emit('click:template', templateName);
		},
		removeImage() {
			this.$emit('click:clear', this.image);
		}
	}
}
</script>
<style lang="scss">
.static-card-image-uploader .shapla-file-uploader {
	display: flex;
	flex-direction: column;
	height: 100%;
	justify-content: center;
}
</style>
