import CommonPublicHolidays from "./CommonPublicHolidays.vue";
import OtherHolidays from "./OtherHolidays.vue";
import {createApp} from "vue";

const elCommonPublicHolidays = document.getElementById('dispatch_timer_common_public_holidays_app');
if (elCommonPublicHolidays) {
  const app = createApp(CommonPublicHolidays)
  app.mount(elCommonPublicHolidays);
}

const elOtherHolidays = document.getElementById('dispatch_timer_special_holidays_app');
if (elOtherHolidays) {
  const app = createApp(OtherHolidays)
  app.mount(elOtherHolidays);
}