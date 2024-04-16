import SettingsPage from "./SettingsPage.vue";
import {createApp} from "vue";

const rootEl = document.querySelector('#stability-ai-admin');
if (rootEl) {
	const app = createApp(SettingsPage)
	app.mount(rootEl);
}
