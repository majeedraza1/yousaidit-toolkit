<template>
	<div class="flex -m-2">
		<div class="sm:w-full md:w-1/2 p-2">
			<image-container v-if="!hasImage">
				<file-uploader
					class="static-card-image-uploader"
					:url="attachment_upload_url"
					@before:send="handleBeforeSend"
					@success="handleImageUpload"
				/>
			</image-container>
			<image-container v-if="hasImage">
				<img :src="previewImage.src"/>
			</image-container>
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
			</div>
		</div>
	</div>
</template>

<script>
import {FileUploader, imageContainer} from "shapla-vue-components";

export default {
	name: "StaticCardUploader",
	components: {FileUploader, imageContainer},
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
