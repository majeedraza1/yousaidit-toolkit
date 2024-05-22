import {createApp} from "vue";
import App from './App.vue';

const myAccountReminderEl = document.getElementById('yousaidit_my_account_reminders');
if (myAccountReminderEl) {
  const app = createApp(App);
  app.mount(myAccountReminderEl);
}
