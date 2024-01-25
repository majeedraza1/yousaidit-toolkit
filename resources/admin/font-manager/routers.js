import Vue from 'vue';
import VueRouter from 'vue-router';
import PagePreInstalledFonts from './pages/PagePreInstalledFonts.vue';
import ExtraFontsListPage from './pages/ExtraFontsListPage.vue';

Vue.use(VueRouter);

const routes = [
	{path: '/', name: 'PagePreInstalledFonts', component: PagePreInstalledFonts},
	{path: '/extra', name: 'ExtraFontsListPage', component: ExtraFontsListPage},
];

const fontManagerRouter = () => {
	return new VueRouter({
		routes: routes
	});
}

export default fontManagerRouter;
