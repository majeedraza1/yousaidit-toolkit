import {createRouter, createWebHashHistory} from 'vue-router';
import Products from './pages/Products.vue';
import ShipStationOrders from './pages/ShipStationOrders.vue';

const routes = [
  {path: '/', name: 'Products', component: Products},
  {path: '/orders', name: 'Orders', component: ShipStationOrders},
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
