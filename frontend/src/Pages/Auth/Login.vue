<script setup>
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
    email: '',
    password: '',
});

const errors = ref({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        await authStore.login(form.value);
        router.push('/dashboard');
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else if (error.response?.data?.message) {
            errors.value = { email: [error.response.data.message] };
        } else {
            errors.value = { email: ['Login failed. Please try again.'] };
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <RouterLink to="/" class="text-2xl font-bold">
                    <span class="text-purple-400">Exchange</span>
                    <span class="text-white">Mini</span>
                </RouterLink>
            </div>
            
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 shadow-xl">
                <h2 class="text-2xl font-bold text-white mb-6 text-center">Welcome back</h2>
                
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-4 py-3 rounded-lg bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                            placeholder="you@example.com"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-400">{{ errors.email[0] }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            class="w-full px-4 py-3 rounded-lg bg-white/5 border border-white/10 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                            placeholder="••••••••"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-400">{{ errors.password[0] }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="w-full py-3 px-4 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="processing">Signing in...</span>
                        <span v-else>Sign in</span>
                    </button>
                </form>

                <p class="mt-6 text-center text-gray-400">
                    Don't have an account?
                    <RouterLink to="/register" class="text-purple-400 hover:text-purple-300 font-medium">
                        Sign up
                    </RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>
