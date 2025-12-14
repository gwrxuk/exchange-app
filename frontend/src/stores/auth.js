import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref(null);
    const token = ref(localStorage.getItem('token') || null);
    const loading = ref(false);
    const initialized = ref(false);

    // Getters
    const isAuthenticated = computed(() => !!token.value && !!user.value);

    // Set axios auth header when token changes
    function setAuthHeader(newToken) {
        if (newToken) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
            localStorage.setItem('token', newToken);
            // Update Echo auth headers
            if (window.updateEchoAuth) {
                window.updateEchoAuth(newToken);
            }
        } else {
            delete axios.defaults.headers.common['Authorization'];
            localStorage.removeItem('token');
            if (window.updateEchoAuth) {
                window.updateEchoAuth(null);
            }
        }
    }

    // Initialize auth header from stored token
    if (token.value) {
        setAuthHeader(token.value);
    }

    // Actions
    async function fetchUser() {
        if (loading.value || !token.value) {
            initialized.value = true;
            return;
        }

        loading.value = true;
        try {
            const response = await axios.get('/api/me');
            user.value = response.data;
            initialized.value = true;
        } catch (error) {
            // Token invalid or expired
            token.value = null;
            user.value = null;
            setAuthHeader(null);
            initialized.value = true;
        } finally {
            loading.value = false;
        }
    }

    async function login(credentials) {
        const response = await axios.post('/api/login', credentials);
        token.value = response.data.token;
        user.value = response.data.user;
        setAuthHeader(response.data.token);
        initialized.value = true;
        return response.data;
    }

    async function register(data) {
        const response = await axios.post('/api/register', data);
        token.value = response.data.token;
        user.value = response.data.user;
        setAuthHeader(response.data.token);
        initialized.value = true;
        return response.data;
    }

    async function logout() {
        try {
            await axios.post('/api/logout');
        } catch (error) {
            // Ignore logout errors
        }
        token.value = null;
        user.value = null;
        setAuthHeader(null);
        initialized.value = false;
    }

    async function refresh() {
        try {
            const response = await axios.post('/api/refresh');
            token.value = response.data.token;
            setAuthHeader(response.data.token);
            return response.data;
        } catch (error) {
            await logout();
            throw error;
        }
    }

    function reset() {
        token.value = null;
        user.value = null;
        setAuthHeader(null);
        initialized.value = false;
        loading.value = false;
    }

    return {
        // State
        user,
        token,
        loading,
        initialized,
        // Getters
        isAuthenticated,
        // Actions
        fetchUser,
        login,
        register,
        logout,
        refresh,
        reset,
    };
});
