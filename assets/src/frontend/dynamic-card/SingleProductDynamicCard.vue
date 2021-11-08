<template>
	<modal :active="show_dynamic_card_editor" @close="show_dynamic_card_editor = false" title="Customize"
		   content-size="full" :show-card-footer="false">
		<div class="w-full h-full flex space-x-4">
			<div>
				<card-web-viewer
					v-if="show_dynamic_card_editor && Object.keys(payload).length"
					:args="payload"
					:upload-url="uploadUrl"
					:images="images"
					@edit:section="handleEditSection"
				/>
			</div>
			<div class="flex flex-col justify-between">
				<div class="flex flex-col space-y-4">
					<shapla-button theme="secondary" fullwith>Add a message</shapla-button>
					<shapla-button theme="primary" fullwith>Add to basket and continue shopping</shapla-button>
				</div>
				<div>
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
			</div>
		</div>
	</modal>
</template>

<script>
import axios from "axios";
import {modal, shaplaButton} from "shapla-vue-components";
import CardWebViewer from "@/components/DynamicCardPreview/CardWebViewer";

export default {
	name: "SingleProductDynamicCard",
	components: {CardWebViewer, modal, shaplaButton},
	data() {
		return {
			product_id: 0,
			show_dynamic_card_editor: false,
			payload: {},
			images: [],
		}
	},
	computed: {
		uploadUrl() {
			return '';
		}
	},
	methods: {
		handleEditSection(section) {
		},
		loadCardInfo() {
			axios.get(StackonetToolkit.restRoot + `/dynamic-cards/${this.product_id}`).then(response => {
				this.payload = response.data.data;
			});
		}
	},
	mounted() {
		let el = document.querySelector('#dynamic-card-container');
		if (el) {
			this.product_id = parseInt(el.dataset.productId);
		}

		let btn = document.querySelector('.button--customize-dynamic-card');
		if (btn) {
			btn.addEventListener('click', event => {
				event.preventDefault();
				this.show_dynamic_card_editor = true;
				this.loadCardInfo();
			});
		}
		console.log('Single Product Dynamic.')
	}
}
</script>

<style scoped>

</style>
