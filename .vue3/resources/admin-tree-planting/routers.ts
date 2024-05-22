import {createRouter, createWebHashHistory} from 'vue-router';
import Purchases from './pages/Purchases.vue';
import InQueue from './pages/InQueue.vue';

const routes = [
  {path: '/', name: 'Purchases', component: Purchases},
  {path: '/queue', name: 'InQueue', component: InQueue},
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
