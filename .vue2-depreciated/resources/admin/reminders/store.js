import Vuex from 'vuex'
import Vue from 'vue'
import axios from "@/utils/axios";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";

Vue.use(Vuex);

const getReminders = (page = 1, per_page = 50) => {
    return new Promise(resolve => {
        Spinner.show();
        axios.get('admin/reminders', {
            params: {page, per_page}
        }).then(response => {
            resolve(response.data.data);
        }).finally(() => {
            Spinner.hide();
        })
    })
}

const getRemindersQueues = (status = 'all', page = 1, per_page = 50) => {
    return new Promise(resolve => {
        Spinner.show();
        axios
            .get('admin/reminders-queue', {params: {page, per_page, status}})
            .then(response => {
                resolve(response.data.data);
            })
            .finally(() => {
                Spinner.hide();
            })
    })
}

const reminderStore = function () {
    return new Vuex.Store({
        // Same as Vue data
        state: {
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
            SET_REMINDERS_CURRENT_PAGE(state, page) {
                state.reminders_pagination.current_page = page;
            },
            SET_REMINDERS_QUEUE_CURRENT_PAGE(state, page) {
                state.reminders_queues_pagination.current_page = page;
            }
        },

        // Same as Vue methods
        actions: {
            getReminders({state}) {
                getReminders(state.reminders_pagination.current_page).then(data => {
                    state.reminders = data.reminders;
                    state.reminders_pagination = data.pagination;
                    state.users = data.users;
                })
            },
            getRemindersQueues({state, commit}, status = 'all') {
                return new Promise((resolve) => {
                    getRemindersQueues(status, state.reminders_queues_pagination.current_page).then(data => {
                        state.reminders_queues = data.items;
                        state.reminders_queues_pagination = data.pagination;
                        resolve(data);
                    })
                });
            },
            getRemindersGroups({state, commit}) {
                Spinner.show();
                axios.get('admin/reminders/groups').then(response => {
                    const data = response.data.data;
                    state.reminders_groups = data.items;
                    state.product_cats = data.product_cats;
                }).finally(() => {
                    Spinner.hide();
                })
            },
            createReminderGroup({state, commit, dispatch}, data) {
                return new Promise(resolve => {
                    Spinner.show();
                    axios.post('admin/reminders/groups', data).then((response) => {
                        dispatch('getRemindersGroups');
                        resolve(response.data.data);
                    }).finally(() => {
                        Spinner.hide();
                    })
                })
            },
            updateReminderGroup({commit, dispatch}, data) {
                return new Promise(resolve => {
                    Spinner.show();
                    axios.put('admin/reminders/groups/' + data.id, data).then((response) => {
                        resolve(response.data.data);
                        dispatch('getRemindersGroups');
                    }).finally(() => {
                        Spinner.hide();
                    })
                })
            },
            deleteReminderGroup({commit, dispatch}, id) {
                return new Promise(resolve => {
                    Dialog.confirm('Are you sure to delete this reminder group?').then(() => {
                        Spinner.show();
                        axios.delete('admin/reminders/groups/' + id).then(() => {
                            dispatch('getRemindersGroups');
                            resolve(true);
                            Notify.success('Reminder group has been deleted successfully.', 'Success!');
                        }).finally(() => {
                            Spinner.hide();
                        })
                    })
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
