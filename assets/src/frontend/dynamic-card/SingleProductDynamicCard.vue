<template>
	<div></div>
</template>

<script>
import axios from "axios";

export default {
	name: "SingleProductDynamicCard",
	data() {
		return {
			product_id: 0,
			show_dynamic_card_editor: false
		}
	},
	methods: {
		loadCardInfo() {
			axios.get(StackonetToolkit.restRoot + `/dynamic-cards/${this.product_id}`).then(response => {
				console.log(response);
			})
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
	}
}
</script>

<style scoped>

</style>
