import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    server: {
        host: 'localhost',
        port: 5174,
        strictPort: true,
        proxy: {
            '^/backend(?=/|$)': {
                target: 'http://127.0.0.1:8000',
                rewrite: (path) => path.replace(/^\/backend(?=\/|$)/, ''),
            },
        },
    },
});
