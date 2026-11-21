import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        proxy: {
            '/backend': {
                target: 'http://localhost/proyecto-intefacesweb-develop',
            },
        },
    },
});
