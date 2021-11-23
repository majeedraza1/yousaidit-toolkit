import Vue from "vue";
import SingleProductDynamicCard from "./SingleProductDynamicCard.vue";
import axios from "axios";

if (window.StackonetToolkit && window.StackonetToolkit.restNonce) {
	axios.defaults.headers.common['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
}

const dynamicCardEl = document.querySelector('#dynamic-card');
if (dynamicCardEl) {
	console.log(dynamicCardEl);
	new Vue({
		el: dynamicCardEl,
		render: h => h(SingleProductDynamicCard)
	});
}
