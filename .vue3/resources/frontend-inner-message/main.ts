import './style.scss';
import {createApp} from "vue";
import SingleProductDynamicCard from "./SingleProductDynamicCard.vue";
import InnerMessage from "./InnerMessage.vue";

const dynamicCardEl = document.querySelector<HTMLDivElement>('#dynamic-card');
if (dynamicCardEl) {
  createApp(SingleProductDynamicCard).mount(dynamicCardEl);
}

let innerMessageEl = document.querySelector<HTMLDivElement>('#inner-message');
if (innerMessageEl) {
  document.querySelector('body').classList.add('has-inner-message');
  createApp(InnerMessage).mount(innerMessageEl);
}