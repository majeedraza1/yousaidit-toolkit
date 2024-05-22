import ReactDOM from "react-dom";
import {StrictMode} from "react";
import CommonPublicHolidays from './CommonPublicHolidays'
import OtherHolidays from './OtherHolidays'

const elCommonPublicHolidays = document.getElementById('dispatch_timer_common_public_holidays_app');
if (elCommonPublicHolidays) {
  ReactDOM.render(<StrictMode><CommonPublicHolidays/></StrictMode>, elCommonPublicHolidays);
}

const elOtherHolidays = document.getElementById('dispatch_timer_special_holidays_app');
if (elOtherHolidays) {
  ReactDOM.render(<StrictMode><OtherHolidays/></StrictMode>, elOtherHolidays);
}