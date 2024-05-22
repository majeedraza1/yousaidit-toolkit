import {createRouter, createWebHashHistory} from 'vue-router';
import Dispatcher from './pages/Dispatcher.vue';
import PrintCards from './pages/PrintCards.vue';
import DispatchOrders from './pages/DispatchOrders.vue';
import CompleteOrders from './pages/CompleteOrders.vue';
import PagePackingSlip from './pages/PagePackingSlip.vue';

const routes = [
  {path: '/', name: 'Dispatcher', component: Dispatcher},
  {path: '/print-cards', name: 'PrintCards', component: PrintCards},
  {path: '/dispatch-orders', name: 'DispatchOrders', component: DispatchOrders},
  {path: '/complete-orders', name: 'CompleteOrders', component: CompleteOrders},
  {path: '/packing-slip', name: 'packing-slip', component: PagePackingSlip},
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
