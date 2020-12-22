import Vuex from 'vuex'
import Vue from 'vue'
import axios from "axios";
import printPage from "./print";

Vue.use(Vuex);

const orderDispatcherStore = function () {
	return new Vuex.Store({
		// Same as Vue data
		state: {
			loading: false,
			notification: {},
			orders: [],
			checked_items: [],
			order_pagination: {currentPage: 1, totalCount: 0, limit: 100},
			current_page: 1,
			card_size: 'square',
			inner_message: false,
			carriers: [],
			orderStatus: 'awaiting_shipment',
		},

		// Commit + track state changes
		mutations: {
			SET_LOADING_STATUS(state, loading) {
				state.loading = loading;
			},
			SET_NOTIFICATION(state, notification) {
				state.notification = notification;
			},
			SET_ORDERS(state, orders) {
				state.orders = orders;
			},
			SET_ORDER_PAGINATION(state, order_pagination) {
				state.order_pagination = order_pagination;
			},
			SET_CURRENT_PAGE(state, current_page) {
				state.current_page = current_page;
			},
			SET_CARD_SIZE(state, card_size) {
				state.card_size = card_size;
			},
			SET_INNER_MESSAGE(state, inner_message) {
				state.inner_message = inner_message;
			},
			SET_CHECKED_ITEMS(state, checked_items) {
				state.checked_items = checked_items;
			},
			SET_CARRIERS(state, carriers) {
				state.carriers = carriers;
			},
			SET_ORDER_STATUS(state, orderStatus) {
				state.orderStatus = orderStatus;
			},
		},

		// Same as Vue methods
		actions: {
			getOrders({state, commit}, force = false) {
				commit('SET_LOADING_STATUS', true);
				axios.get(Stackonet.root + '/orders', {
					params: {
						page: state.current_page,
						card_size: state.card_size,
						inner_message: state.inner_message,
						orderStatus: state.orderStatus,
						force: force
					}
				}).then(response => {
					let data = response.data.data;
					commit('SET_ORDERS', data.items);
					commit('SET_ORDER_PAGINATION', data.pagination);
					commit('SET_LOADING_STATUS', false);
				}).catch(error => {
					console.log(error);
					commit('SET_LOADING_STATUS', false);
				})
			},
			getCarriers({commit}) {
				commit('SET_LOADING_STATUS', true);
				axios.get(Stackonet.root + '/carriers').then(response => {
					let data = response.data.data;
					commit('SET_CARRIERS', data.items);
					commit('SET_LOADING_STATUS', false);
				}).catch(error => {
					console.log(error);
					commit('SET_LOADING_STATUS', false);
				})
			},
			getOrder({commit}, orderId) {
				return new Promise(resolve => {
					axios.get(Stackonet.root + '/orders/' + orderId).then(response => {
						let data = response.data.data;
						resolve(data);
					}).catch(error => {
						console.log(error);
					})
				})
			},
			refreshFromShipStation({dispatch}) {
				dispatch('getOrders', true);
			},
			paginate({dispatch, commit}, page = 1) {
				commit('SET_CURRENT_PAGE', page);
				dispatch('getOrders');
			},
			mergePackingSlip({state}) {
				let url = ajaxurl + '?action=stackonet_order_packing_slips&ids=' + state.checked_items.toString();
				window.open(url, '_blank');
			},
			showInvoice({state}, orderId) {
				let url = ajaxurl + '?action=stackonet_order_packing_slip&id=' + orderId;
				window.open(url, '_blank');
			},
			printAddress({state}, orderId) {
				let url = ajaxurl + '?action=print_order_address&id=' + orderId;
				// window.open(url, '_blank');
				printPage.printPage(url);
			},
			dispatch({state, commit}, data) {
				return new Promise(resolve => {
					commit('SET_LOADING_STATUS', true);
					axios.post(Stackonet.root + '/dispatch', data).then(response => {
						let data = response.data.data;
						resolve(data);
						commit('SET_LOADING_STATUS', false);
						commit('SET_NOTIFICATION', {
							message: 'Order marked as shipped.',
							type: 'success',
							title: 'Success!'
						});
					}).catch(error => {
						if (error.response.data.message) {
							commit('SET_NOTIFICATION', {
								message: error.response.data.message,
								type: 'error',
								title: 'Error!'
							});
						}
						commit('SET_LOADING_STATUS', false);
					})
				})
			}
		},

		// Save as Vue computed property
		getters: {},
	});
};

export default orderDispatcherStore;
