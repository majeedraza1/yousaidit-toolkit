import Vue from 'vue';
import axios from 'axios'
import App from './App'
import router from './routers.js';
import orderDispatcherStore from './order-dispatcher/store.js';
import menuFix from "./utils/admin-menu-fix.js";
import {Dialog} from 'shapla-vue-components'

Vue.use(Dialog);

jQuery.ajaxSetup({
	beforeSend: function (xhr) {
		xhr.setRequestHeader('X-WP-Nonce', window.Stackonet.nonce);
	}
});

axios.defaults.headers.common['X-WP-Nonce'] = window.Stackonet.nonce;

let el = document.querySelector('#stackonet_toolkit_admin');
if (el) {
	new Vue({el: el, store: orderDispatcherStore, router: router, render: h => h(App)});
}

// fix the admin menu for the slug "stackonet-art-work"
menuFix('stackonet-art-work');


document.addEventListener("DOMContentLoaded", function () {
	var elementsArray = document.querySelectorAll('[id^="wpforms-form-"]');
	elementsArray.forEach(function (elem) {
		elem.addEventListener("submit", function (e) {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push({
				event: "wpFormSubmit",
				wpFormElement: event.target
			});
		});
	});
});
