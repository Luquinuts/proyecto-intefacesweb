import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    server: {
        port: 5174,
        strictPort: true,
        proxy: {
            '/backend': {
                target: 'http://localhost/proyecto-intefacesweb-develop',
            },
        },
    },
});
