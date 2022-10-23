<template>
	<div class="flex w-full h-full">
		<div v-if="messageType === ''" class="flex flex-col justify-center items-center w-full h-full p-4 lg:p-8">
			<div
				@click="messageType = 'video'"
				class="border border-solid border-gray-200 hover:border-gray-500 cursor-pointer inline-flex items-center space-x-2 rounded px-4 py-2">
				<div class="w-8 h-8">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="fill-current">
						<g>
							<rect fill="none" height="24" width="24" y="0"/>
						</g>
						<g>
							<g>
								<polygon points="9.5,7.5 9.5,16.5 16.5,12"/>
								<path
									d="M20,4H4C2.9,4,2,4.9,2,6v12c0,1.1,0.9,2,2,2h16c1.1,0,2-0.9,2-2V6C22,4.9,21.1,4,20,4z M20,18.01H4V5.99h16V18.01z"/>
							</g>
						</g>
					</svg>
				</div>
				<div>
					Add a video message
					<div v-html="videoMessagePriceHTML"></div>
				</div>
			</div>
			<div class="text-sm mt-1">Your video will play when they scan the QR code printed on the inside page.</div>

			<div
				@click="messageType = 'text'"
				class="border border-solid border-gray-200 hover:border-gray-500 cursor-pointer inline-flex items-center rounded px-4 py-2 mt-8">
				Add text
			</div>
		</div>
		<div v-if="messageType === 'text'" class="dynamic-card--editable-content-container w-full">
			<editable-content
				placeholder="Please click here to write your message"
				:font-family="innerMessage.font_family"
				:font-size="innerMessage.font_size"
				:text-align="innerMessage.alignment"
				:color="innerMessage.color"
				v-model="innerMessage.message"
				:card-size="card_size"
				@lengthError="onLengthError"
			/>
			<div v-if="showLengthError" class="has-error p-2 my-4 absolute bottom-0">
				Oops... your message is too long, please keep inside the box.
			</div>
		</div>
		<div v-if="messageType === 'video'" class="flex flex-col justify-center items-center w-full h-full p-4 lg:p-8">
			<template v-if="videos.length < 1">
				<file-uploader
					:url="uploadUrl"
					@before:send="beforeSendEvent"
					@success="finishedEvent"
					@failed="handleFileUploadFailed"
				/>
				<p class="mt-2">
					<shapla-button size="small" outline theme="primary" @click="messageType = ''">Back</shapla-button>
				</p>
			</template>
			<template v-if="videos.length">
				<image-container :width-ratio="videos[0].width" :height-ratio="videos[0].height">
					<video :width="videos[0].width" :height="videos[0].height" controls>
						<source :src="videos[0].url" :type="videos[0].type">
					</video>
				</image-container>
				<p class="mt-2">
					<shapla-button size="small" outline theme="primary" @click="clearVideo">Clear Video</shapla-button>
				</p>
			</template>
		</div>
	</div>
</template>

<script>
import EditableContent from "@/frontend/inner-message/EditableContent";
import {FileUploader, imageContainer, shaplaButton} from "shapla-vue-components";
import axios from "axios";

export default {
	name: "VideoInnerMessage",
	components: {EditableContent, FileUploader, imageContainer, shaplaButton},
	props: {
		product_id: {default: 0},
		card_size: {default: ''},
		innerMessage: {default: ''},
	},
	data() {
		return {
			messageType: '',
			showLengthError: false,
			videos: [],
		}
	},
	computed: {
		videoMessagePriceHTML() {
			return window.StackonetToolkit.videoMessagePriceHTML;
		},
		uploadUrl() {
			return StackonetToolkit.restRoot + '/dynamic-cards/video';
		},
		isUserLoggedIn() {
			return window.StackonetToolkit.isUserLoggedIn || false;
		},
	},
	methods: {
		onLengthError(error) {
			this.showLengthError = error;
		},
		beforeSendEvent(xhr) {
			if (window.StackonetToolkit.restNonce) {
				xhr.setRequestHeader('X-WP-Nonce', window.StackonetToolkit.restNonce);
			}
		},
		finishedEvent(fileObject, response) {
			if (response.success) {
				this.videos.unshift(response.data);
				localStorage.setItem(`__gust_video_${this.product_id}`, response.data.id.toString());
			}
		},
		handleFileUploadFailed(fileObject, response) {
			if (response.message) {
				this.notifications = {type: 'error', title: 'Error!', message: response.message};
			}
		},
		fetchVideos() {
			let config = {};
			if (!this.isUserLoggedIn) {
				const localStorageData = localStorage.getItem(`__gust_video_${this.product_id}`);
				if (localStorageData) {
					config = {params: {videos: [parseInt(localStorageData)]}}
				}
			}
			axios.get(this.uploadUrl, config).then(response => {
				if (response.data.data) {
					this.videos = response.data.data;
				}
			})
		},
		clearVideo() {
			this.videos = [];
			localStorage.removeItem(`__gust_video_${this.product_id}`);
		},
	},
	mounted() {
		this.fetchVideos();
	}
}
</script>

<style scoped>

</style>
