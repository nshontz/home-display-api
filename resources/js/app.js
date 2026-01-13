import './bootstrap';
import '../css/pages.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

const app = createApp(App);
app.use(router);
app.mount('#app');

document.title = "Menu";
