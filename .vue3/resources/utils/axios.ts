import axios, {AxiosInstance} from "axios";

const axiosArgs = {
  baseURL: window.StackonetToolkit.restRoot,
  headers: {},
};
if (window.StackonetToolkit && window.StackonetToolkit.restNonce) {
  axiosArgs.headers = {'X-WP-Nonce': window.StackonetToolkit.restNonce};
}

const http: AxiosInstance = axios.create(axiosArgs);

export default http;
