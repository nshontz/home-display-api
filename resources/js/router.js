import { createRouter, createWebHistory } from 'vue-router';
import HomeDisplay from './components/HomeDisplay.vue';
import DinnerList from './components/DinnerList.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeDisplay,
        props: { homeFeed: '/api' }
    },
    {
        path: '/dinner-list',
        name: 'dinner-list',
        component: DinnerList
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
