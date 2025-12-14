import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';

// Pages
import Welcome from '../Pages/Welcome.vue';
import Login from '../Pages/Auth/Login.vue';
import Register from '../Pages/Auth/Register.vue';
import OrderForm from '../Pages/OrderForm.vue';
import Wallet from '../Pages/Wallet.vue';

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
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { guest: true },
    },
    {
        path: '/order',
        name: 'order',
        component: OrderForm,
        meta: { requiresAuth: true },
    },
    {
        path: '/wallet',
        name: 'wallet',
        component: Wallet,
        meta: { requiresAuth: true },
    },
    // Redirect /dashboard to /order for backwards compatibility
    {
        path: '/dashboard',
        redirect: '/order',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const { isAuthenticated, initialized } = storeToRefs(authStore);
    
    // Only fetch user if going to a protected route and haven't checked yet
    if (to.meta.requiresAuth && !initialized.value) {
        await authStore.fetchUser();
    }
    
    // Redirect to login if route requires auth and user is not authenticated
    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return next('/login');
    }
    
    // Redirect to order page if already authenticated and trying to access guest pages
    if (to.meta.guest && initialized.value && isAuthenticated.value) {
        return next('/order');
    }
    
    next();
});

export default router;
