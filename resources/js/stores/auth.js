import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
    }),
    actions: {
        async fetchUser() {
            try {
                const response = await axios.get('/api/user');
                this.user = response.data;
                this.isAuthenticated = true;
            } catch (error) {
                this.user = null;
                this.isAuthenticated = false;
            }
        },
        async login(credentials) {
            await axios.get('/sanctum/csrf-cookie');
            await axios.post('/login', credentials);
            await this.fetchUser();
        },
        async logout() {
            await axios.post('/logout');
            this.user = null;
            this.isAuthenticated = false;
        },
    },
});

