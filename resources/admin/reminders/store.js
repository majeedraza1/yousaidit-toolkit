import Vuex from 'vuex'
import Vue from 'vue'
import axios from "axios";

Vue.use(Vuex);

const reminderStore = function () {
	return new Vuex.Store({
		// Same as Vue data
		state: {
			loading: false,
			notification: {},
			reminders: [],
			users: [],
			reminders_pagination: {
				current_page: 1,
				per_page: 50,
			},
			reminders_groups: [],
			product_cats: [],
			reminders_queues: [],
			reminders_queues_pagination: {
				current_page: 1,
				per_page: 50,
			},
		},

		// Commit + track state changes
		mutations: {
			SET_LOADING_STATUS(state, loading) {
				state.loading = loading;
			},
			SET_NOTIFICATION(state, notification) {
				state.notification = notification;
			},
			SET_REMINDERS_CURRENT_PAGE(state, page) {
				state.reminders_pagination.current_page = page;
			},
			SET_REMINDERS_QUEUE_CURRENT_PAGE(state, page) {
				state.reminders_queues_pagination.current_page = page;
			}
		},

		// Same as Vue methods
		actions: {
			getReminders({state, commit}) {
				commit('SET_LOADING_STATUS', true);
				axios.get(window.StackonetToolkit.restRoot + '/admin/reminders', {
					params: {
						page: state.reminders_pagination.current_page,
						per_page: state.reminders_pagination.per_page,
					}
				}).then(response => {
					const data = response.data.data;
					state.reminders = data.reminders;
					state.reminders_pagination = data.pagination;
					state.users = data.users;
				}).finally(() => {
					commit('SET_LOADING_STATUS', false);
				})
			},
			getRemindersQueues({state, commit}, status = 'all') {
				return new Promise((resolve) => {
					commit('SET_LOADING_STATUS', true);
					axios.get(window.StackonetToolkit.restRoot + '/admin/reminders-queue', {
						params: {
							page: state.reminders_queues_pagination.current_page,
							per_page: state.reminders_queues_pagination.per_page,
							status: status
						}
					}).then(response => {
						const data = response.data.data;
						state.reminders_queues = data.items;
						state.reminders_queues_pagination = data.pagination;
						resolve(data);
					}).finally(() => {
						commit('SET_LOADING_STATUS', false);
					})
				});
			},
			getRemindersGroups({state, commit}) {
				commit('SET_LOADING_STATUS', true);
				axios.get(window.StackonetToolkit.restRoot + '/admin/reminders/groups').then(response => {
					const data = response.data.data;
					state.reminders_groups = data.items;
					state.product_cats = data.product_cats;
				}).finally(() => {
					commit('SET_LOADING_STATUS', false);
				})
			},
			createReminderGroup({state, commit, dispatch}, data) {
				return new Promise(resolve => {
					commit('SET_LOADING_STATUS', true);
					axios.post(window.StackonetToolkit.restRoot + '/admin/reminders/groups', data).then((response) => {
						dispatch('getRemindersGroups');
						resolve(response.data.data);
					}).finally(() => {
						commit('SET_LOADING_STATUS', false);
					})
				})
			},
			updateReminderGroup({commit, dispatch}, data) {
				return new Promise(resolve => {
					commit('SET_LOADING_STATUS', true);
					axios.put(window.StackonetToolkit.restRoot + '/admin/reminders/groups/' + data.id, data).then((response) => {
						resolve(response.data.data);
						dispatch('getRemindersGroups');
					}).finally(() => {
						commit('SET_LOADING_STATUS', false);
					})
				})
			},
			deleteReminderGroup({commit, dispatch}, id) {
				commit('SET_LOADING_STATUS', true);
				axios.delete(window.StackonetToolkit.restRoot + '/admin/reminders/groups/' + id).then(() => {
					dispatch('getRemindersGroups');
				}).finally(() => {
					commit('SET_LOADING_STATUS', false);
				})
			}
		},

		// Save as Vue computed property
		getters: {
			remind_days_options() {
				return [
					{value: 3, label: '3 days before'},
					{value: 5, label: '5 days before'},
					{value: 7, label: '7 days before'},
					{value: 10, label: '10 days before'},
					{value: 15, label: '15 days before'},
					{value: 30, label: '30 days before'},
				]
			}
		},
	});
}

export default reminderStore;
