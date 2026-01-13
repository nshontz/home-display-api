import { createRouter, createWebHistory } from 'vue-router';
import HomeDisplay from './components/HomeDisplay.vue';
import DinnerList from './components/DinnerList.vue';
import Stats from './components/Stats.vue';

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
    },
    {
        path: '/stats',
        name: 'stats',
        component: Stats
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
