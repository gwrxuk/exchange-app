import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../Pages/Dashboard.vue';
import Login from '../Pages/Auth/Login.vue';
import Register from '../Pages/Auth/Register.vue';
import Welcome from '../Pages/Welcome.vue';

const routes = [
    {
        path: '/',
        name: 'welcome',
        component: Welcome,
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;

