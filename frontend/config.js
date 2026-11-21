const defaultApiBaseUrl = 'http://localhost/proyecto-intefacesweb-develop/backend';

export const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || defaultApiBaseUrl).replace(/\/+$/, '');

export function apiUrl(path) {
    return `${API_BASE_URL}/${path.replace(/^\/+/, '')}`;
}
