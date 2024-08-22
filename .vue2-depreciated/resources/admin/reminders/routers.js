import Vue from 'vue';
import VueRouter from 'vue-router';
import Reminders from './pages/Reminders';
import RemindersGroups from './pages/RemindersGroups';
import RemindersQueue from './pages/RemindersQueue';

Vue.use(VueRouter);

const routes = [
	{path: '/', name: 'Reminders', component: Reminders},
	{path: '/groups', name: 'RemindersGroups', component: RemindersGroups},
	{path: '/queue', name: 'Queue', component: RemindersQueue},
];

const remindersRouter = () => {
	return new VueRouter({
		routes: routes
	});
}

export default remindersRouter;
