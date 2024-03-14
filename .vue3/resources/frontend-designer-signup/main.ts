import {createApp} from "vue";
import DesignerSignup from "./DesignerSignup.vue";
import LoginModal from "./LoginModal.vue";
import './style.scss'

const el = document.querySelector<HTMLDivElement>('#yousaidit-designer-signup');
if (el) {
  createApp(DesignerSignup).mount(el);
}
const elLogin = document.querySelector<HTMLDivElement>('#yousaidit-frontend-login-popup');
if (elLogin) {
  createApp(LoginModal).mount(elLogin);
}
