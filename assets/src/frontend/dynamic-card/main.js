import Vue from "vue";
import SingleProductDynamicCard from "@/frontend/dynamic-card/SingleProductDynamicCard.vue";

const dynamicCardEl = document.querySelector('#dynamic-card');
if (dynamicCardEl) {
	console.log(dynamicCardEl);
	new Vue({
		el: dynamicCardEl,
		render: h => h(SingleProductDynamicCard)
	});
}
