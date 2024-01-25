import Vue from "vue";
import SingleProductDynamicCard from "./SingleProductDynamicCard.vue";

const dynamicCardEl = document.querySelector('#dynamic-card');
if (dynamicCardEl) {
    new Vue({
        el: dynamicCardEl,
        render: h => h(SingleProductDynamicCard)
    });
}
