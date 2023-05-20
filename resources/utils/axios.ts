import axios from 'axios';

declare global {
  interface Window {
    StackonetToolkit: {
      restRoot: string
      restNonce?: string
      occasions: { slug: string; label: string; menu_order?: number; }[];
      recipients: { slug: string; label: string; menu_order?: number; }[];
      topics: { slug: string; label: string; menu_order?: number; }[];
    }
  }
}

const axiosArgs = {
  baseURL: window.StackonetToolkit.restRoot,
  headers: {},
};
if (window.StackonetToolkit && window.StackonetToolkit.restNonce) {
  axiosArgs.headers = {'X-WP-Nonce': window.StackonetToolkit.restNonce};
}

const http = axios.create(axiosArgs);

export default http;
