import axios from 'axios';

const CrudMixin = {
	methods: {
		__commit(type, payload) {
			if (this.$store) {
				this.$store.commit(type, payload);
			} else {
				if (type === 'SET_LOADING_STATUS') {
					this.loading = payload;
				}
				if (type === 'SET_NOTIFICATION') {
					this.notification = payload;
				}
			}
		},
		__show_error(message) {
			this.__commit('SET_NOTIFICATION', {type: 'error', title: 'Error!', message: message});
		},
		__handle_error(errors) {
			this.__commit('SET_LOADING_STATUS', false);
			if (typeof errors.response.data.message === "string") {
				this.__show_error(errors.response.data.message);
			}
		},
		get_items(url, config = {}) {
			return new Promise(resolve => {
				this.__commit('SET_LOADING_STATUS', true);
				axios.get(url, config).then(response => {
					resolve(response.data.data);
					this.__commit('SET_LOADING_STATUS', false);
				}).catch(error => {
					this.__handle_error(error)
				})
			});
		},
		get_item(url, config = {}) {
			return this.get_items(url, config);
		},
		create_item(url, data = [], config = {}) {
			return new Promise(resolve => {
				this.__commit('SET_LOADING_STATUS', true);
				axios.post(url, data, config).then(response => {
					resolve(response.data.data);
					this.__commit('SET_LOADING_STATUS', false);
				}).catch(errors => {
					this.__handle_error(errors);
				})
			});
		},
		update_item(url, data = [], config = {}) {
			return new Promise(resolve => {
				this.__commit('SET_LOADING_STATUS', true);
				axios.put(url, data, config).then(response => {
					resolve(response.data.data);
					this.__commit('SET_LOADING_STATUS', false);
				}).catch(error => {
					this.__handle_error(error);
				})
			});
		},
		delete_item(url, config = {}) {
			return new Promise(resolve => {
				this.__commit('SET_LOADING_STATUS', true);
				axios.delete(url, config).then(response => {
					resolve(response.data.data);
					this.__commit('SET_LOADING_STATUS', false);
				}).catch(error => {
					this.__handle_error(error);
				})
			});
		},
		action_trash(url, id, action) {
			return this.action_batch_trash(url, [id], action);
		},
		action_batch_trash(url, ids, action) {
			if (-1 === ['trash', 'restore', 'delete'].indexOf(action)) {
				return this.__show_error('Only trash, restore and delete are supported.');
			}
			let data = {};
			data[action] = ids;
			return new Promise(resolve => {
				this.__commit('SET_LOADING_STATUS', true);
				axios.post(url, data).then((response) => {
					resolve(response.data.data);
					this.__commit('SET_LOADING_STATUS', false);
				}).catch(errors => {
					this.__handle_error(errors);
				});
			});
		}
	}
};

export {CrudMixin}
export default CrudMixin;
