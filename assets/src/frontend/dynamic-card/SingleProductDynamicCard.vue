<template>
	<modal :active="show_dynamic_card_editor" @close="show_dynamic_card_editor = false" title="Customize"
		   content-size="full">
		<card-web-viewer
			v-if="show_dynamic_card_editor && Object.keys(payload).length"
			:args="payload"
			:upload-url="uploadUrl"
			:images="images"
			@edit:section="handleEditSection"
		/>
	</modal>
</template>

<script>
import axios from "axios";
import {modal} from "shapla-vue-components";
import CardWebViewer from "@/components/DynamicCardPreview/CardWebViewer";

export default {
	name: "SingleProductDynamicCard",
	components: {CardWebViewer, modal},
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
