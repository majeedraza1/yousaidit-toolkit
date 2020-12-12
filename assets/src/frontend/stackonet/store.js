import Vuex from 'vuex'
import Vue from 'vue'

Vue.use(Vuex);

function stackonetFrontendStorage() {
	return new Vuex.Store({
		// Same as Vue data
		state: {
			loading: false,
			notification: {},
		},

		// Commit + track state changes
		mutations: {
			SET_LOADING_STATUS(state, loading) {
				state.loading = loading;
			},
			SET_NOTIFICATION(state, notification) {
				state.notification = notification;
			},
		},

		// Same as Vue methods
		actions: {},

		// Save as Vue computed property
		getters: {},
	});
}

export default stackonetFrontendStorage;
