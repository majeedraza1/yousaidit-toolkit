import axios from 'axios';

const axiosArgs = {
    baseURL: window.StackonetToolkit.restRoot,
    headers: {},
};
if (window.StackonetToolkit && window.StackonetToolkit.restNonce) {
    axiosArgs.headers = {'X-WP-Nonce': window.StackonetToolkit.restNonce};
}

const http = axios.create(axiosArgs);

export default http;
