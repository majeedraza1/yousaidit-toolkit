import Vuex from 'vuex'
import Vue from 'vue'
import axios from "axios";

Vue.use(Vuex);

export default function designersStore() {
	return new Vuex.Store({
		// Same as Vue data
		state: {
			loading: false,
			notification: {},
			designer_id: 0,
			designer: {},
			cards: [],
			cards_statuses: [],
			total_commission: 0,
			unpaid_commission: 0,
			paid_commission: 0,
			unique_customers: 0,
			total_orders: 0,
			commissions: [],
			total_commissions_items: 0,
			revenue_current_page: 1,
		},

		// Commit + track state changes
		mutations: {
			SET_LOADING_STATUS(state, loading) {
				state.loading = loading;
			},
			SET_NOTIFICATION(state, notification) {
				state.notification = notification;
			},
			SET_DESIGNER_ID(state, designer_id) {
				state.designer_id = designer_id;
			},
			SET_DESIGNER(state, designer) {
				state.designer = designer;
			},
			SET_CARDS(state, cards) {
				state.cards = cards;
			},
			SET_CARDS_STATUSES(state, cards_statuses) {
				state.cards_statuses = cards_statuses;
			},
			SET_TOTAL_COMMISSION(state, total_commission) {
				state.total_commission = total_commission;
			},
			SET_UNPAID_COMMISSION(state, unpaid_commission) {
				state.unpaid_commission = unpaid_commission;
			},
			SET_PAID_COMMISSION(state, paid_commission) {
				state.paid_commission = paid_commission;
			},
			SET_UNIQUE_CUSTOMER(state, unique_customers) {
				state.unique_customers = unique_customers;
			},
			SET_COMMISSIONS(state, commissions) {
				state.commissions = commissions;
			},
			SET_TOTAL_ORDERS(state, total_orders) {
				state.total_orders = total_orders;
			},
			SET_TOTAL_COMMISSIONS_ITEMS(state, total_commissions_items) {
				state.total_commissions_items = total_commissions_items;
			},
			SET_REVENUE_CURRENT_PAGE(state, revenue_current_page) {
				state.revenue_current_page = revenue_current_page;
			},
		},

		// Same as Vue methods
		actions: {
			getDesigner({commit, state}) {
				commit('SET_LOADING_STATUS', true);
				axios.get(window.DesignerProfile.restRoot + '/designers/' + state.designer_id).then(response => {
					commit('SET_LOADING_STATUS', false);
					commit('SET_DESIGNER', response.data.data.designer);
					commit('SET_CARDS_STATUSES', response.data.data.statuses);
					commit('SET_TOTAL_COMMISSION', response.data.data.total_commission);
					commit('SET_UNPAID_COMMISSION', response.data.data.unpaid_commission);
					commit('SET_PAID_COMMISSION', response.data.data.paid_commission);
					commit('SET_UNIQUE_CUSTOMER', response.data.data.unique_customers);
					commit('SET_TOTAL_ORDERS', response.data.data.total_orders);
				}).catch(errors => {
					commit('SET_LOADING_STATUS', false);
					let error = errors.response.data;
					if (error.message) {
						commit('SET_NOTIFICATION', {type: 'error', message: error.message});
					}
				});
			},
			getCommission({commit, state}, args) {
				commit('SET_LOADING_STATUS', true);
				axios.get(window.DesignerProfile.restRoot + '/designers/' + state.designer_id + '/commissions', {
					params: {
						report_type: args['type'],
						date_from: args['from'],
						date_to: args['to'],
						page: state.revenue_current_page
					}
				}).then(response => {
					let data = response.data.data
					commit('SET_LOADING_STATUS', false);
					commit('SET_COMMISSIONS', data.commissions);
					commit('SET_TOTAL_COMMISSIONS_ITEMS', data.pagination.total_items);
				}).catch(errors => {
					commit('SET_LOADING_STATUS', false);
					let error = errors.response.data;
					if (error.message) {
						commit('SET_NOTIFICATION', {type: 'error', message: error.message});
					}
				});
			}
		},

		// Save as Vue computed property
		getters: {
			assets_url() {
				return window.Stackonet.assets_url
			},
			card_categories() {
				return window.DesignerProfile.categories
			},
			card_tags() {
				return window.DesignerProfile.tags
			},
			card_attributes() {
				return window.DesignerProfile.attributes
			},
			market_places() {
				return window.DesignerProfile.marketPlaces
			},
			card_sizes() {
				return window.DesignerProfile.card_sizes.map(size => {
					return {
						value: size.slug,
						label: size.name
					}
				});
			},
			user() {
				return window.DesignerProfile.user
			},
			user_card_categories() {
				return window.DesignerProfile.user_card_categories
			},
		},
	});
}
