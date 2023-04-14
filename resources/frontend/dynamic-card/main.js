import Vue from "vue";
import axios from "axios";
import SingleProductDynamicCard from "./SingleProductDynamicCard.vue";
import '@/web-components/DynamicCardCanvas.js'
import dynamicCardStore from "./store";

if (window.StackonetToolkit && window.StackonetToolkit.restNonce) {
	axios.defaults.headers.common['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
}

const dynamicCardEl = document.querySelector('#dynamic-card');
if (dynamicCardEl) {
	console.log(dynamicCardEl);
	new Vue({
		el: dynamicCardEl,
		store: dynamicCardStore,
		render: h => h(SingleProductDynamicCard)
	});
}
