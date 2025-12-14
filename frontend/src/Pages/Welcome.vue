<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
// Use storeToRefs for reactive state - this keeps reactivity when destructuring
const { isAuthenticated, initialized } = storeToRefs(authStore);

// Only check auth once if not already initialized
onMounted(async () => {
    if (!initialized.value) {
        await authStore.fetchUser();
    }
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
        <div class="relative flex min-h-screen flex-col items-center justify-center px-6">
            <!-- Header -->
            <header class="absolute top-0 left-0 right-0 py-6 px-8 flex justify-between items-center">
                <div class="text-2xl font-bold tracking-tight">
                    <span class="text-purple-400">Exchange</span>
                    <span class="text-white">Mini</span>
                </div>
                <nav class="flex gap-4">
                    <template v-if="isAuthenticated">
                        <RouterLink
                            to="/dashboard"
                            class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 transition font-medium"
                        >
                            Dashboard
                        </RouterLink>
                    </template>
                    <template v-else>
                        <RouterLink
                            to="/login"
                            class="px-4 py-2 rounded-lg border border-purple-400 hover:bg-purple-400/10 transition font-medium"
                        >
                            Log in
                        </RouterLink>
                        <RouterLink
                            to="/register"
                            class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 transition font-medium"
                        >
                            Register
                        </RouterLink>
                    </template>
                </nav>
            </header>

            <!-- Hero Section -->
            <main class="text-center max-w-3xl">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Trade Crypto with
                    <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                        Precision
                    </span>
                </h1>
                <p class="text-xl text-gray-300 mb-10 leading-relaxed">
                    A limit-order exchange engine with real-time order matching,
                    atomic execution, and instant notifications.
                </p>
                <div class="flex gap-4 justify-center">
                    <RouterLink
                        v-if="!isAuthenticated"
                        to="/register"
                        class="px-8 py-3 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 transition font-semibold text-lg shadow-lg shadow-purple-500/25"
                    >
                        Get Started
                    </RouterLink>
                    <RouterLink
                        v-else
                        to="/dashboard"
                        class="px-8 py-3 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 transition font-semibold text-lg shadow-lg shadow-purple-500/25"
                    >
                        Go to Dashboard
                    </RouterLink>
                </div>
            </main>

            <!-- Features -->
            <div class="absolute bottom-12 left-0 right-0 px-8">
                <div class="max-w-4xl mx-auto grid grid-cols-3 gap-6 text-center">
                    <div class="p-4">
                        <div class="text-purple-400 text-3xl mb-2">⚡</div>
                        <h3 class="font-semibold mb-1">Real-time Matching</h3>
                        <p class="text-sm text-gray-400">Orders matched instantly with WebSocket updates</p>
                    </div>
                    <div class="p-4">
                        <div class="text-purple-400 text-3xl mb-2">🔒</div>
                        <h3 class="font-semibold mb-1">Atomic Execution</h3>
                        <p class="text-sm text-gray-400">Race-safe balance updates with database locks</p>
                    </div>
                    <div class="p-4">
                        <div class="text-purple-400 text-3xl mb-2">💰</div>
                        <h3 class="font-semibold mb-1">1.5% Commission</h3>
                        <p class="text-sm text-gray-400">Transparent fee structure on matched trades</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
