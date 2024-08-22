import Vuex from 'vuex'
import Vue from 'vue'
import axios from "@/utils/axios";
import printPage from "./print";
import {Notify, Spinner} from "@shapla/vanilla-components";

Vue.use(Vuex);

const orderDispatcherStore = function () {
    return new Vuex.Store({
        // Same as Vue data
        state: {
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
                Spinner.show();
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
                    Spinner.hide();
                }).catch(error => {
                    console.log(error);
                    Spinner.hide();
                })
            },
            getCarriers({commit}) {
                Spinner.show();
                axios.get(Stackonet.root + '/carriers').then(response => {
                    let data = response.data.data;
                    commit('SET_CARRIERS', data.items);
                    Spinner.hide();
                }).catch(error => {
                    console.log(error);
                    Spinner.hide();
                })
            },
            getOrder({commit}, orderId) {
                Spinner.show();
                return new Promise(resolve => {
                    axios.get(Stackonet.root + '/orders/' + orderId).then(response => {
                        let data = response.data.data;
                        resolve(data);
                    }).catch(error => {
                        console.log(error);
                    }).finally(() => {
                        Spinner.hide();
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
                    Spinner.show();
                    axios.post(Stackonet.root + '/dispatch', data).then(response => {
                        let data = response.data.data;
                        resolve(data);
                        Spinner.hide();
                        Notify.success('Order marked as shipped.')
                    }).catch(error => {
                        if (error.response.data.message) {
                            Notify.error(error.response.data.message, 'Error!')
                        }
                        Spinner.hide();
                    })
                })
            }
        },

        // Save as Vue computed property
        getters: {},
    });
};

export default orderDispatcherStore;
