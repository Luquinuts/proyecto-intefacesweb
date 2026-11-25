import { createApp } from 'vue';
import $ from 'jquery';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import './styles.css';
import App from './App.vue';

window.$ = $;
window.jQuery = $;

createApp(App).mount('#app');
