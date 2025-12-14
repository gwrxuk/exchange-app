<script setup>
import { ref, onMounted } from 'vue';
import OrderBook from '@/Components/OrderBook.vue';
import LimitOrderForm from '@/Components/LimitOrderForm.vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const symbol = ref('BTC');
const balance = ref(0);
const assets = ref([]);
const myOrders = ref([]);

const fetchProfile = async () => {
    try {
        const res = await axios.get('/api/profile');
        balance.value = res.data.user.balance;
        assets.value = res.data.assets;
    } catch (e) {
        console.error(e);
    }
};

const fetchMyOrders = async () => {
    try {
        const res = await axios.get('/api/my-orders');
        myOrders.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

const cancelOrder = async (id) => {
    if (!confirm('Cancel order?')) return;
    try {
        await axios.post(`/api/orders/${id}/cancel`);
        fetchProfile();
        fetchMyOrders();
    } catch (e) {
        alert(e.response?.data?.error || e.message);
    }
};

onMounted(() => {
    fetchProfile();
    fetchMyOrders();
    
    if (authStore.user && authStore.user.id) {
        Echo.private(`user.${authStore.user.id}`)
            .listen('OrderMatched', (e) => {
                console.log('OrderMatched', e);
                fetchProfile();
                fetchMyOrders();
            });
    }
});

const refreshAll = () => {
    fetchProfile();
    fetchMyOrders();
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center font-bold text-xl">
                            Exchange
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button @click="authStore.logout" class="text-gray-500 hover:text-gray-700">Logout</button>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
            </div>
        </header>

        <main class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Wallet Overview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <h3 class="text-lg font-bold mb-4">Wallet</h3>
                    <div class="flex gap-8">
                        <div>
                            <span class="text-gray-500">USD Balance:</span>
                            <span class="font-mono text-xl ml-2">${{ parseFloat(balance).toFixed(2) }}</span>
                        </div>
                        <div v-for="asset in assets" :key="asset.id">
                            <span class="text-gray-500">{{ asset.symbol }}:</span>
                            <span class="font-mono text-xl ml-2">{{ parseFloat(asset.amount).toFixed(4) }}</span>
                            <span class="text-xs text-gray-400 ml-1">(Locked: {{ parseFloat(asset.locked_amount).toFixed(4) }})</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Order Form -->
                    <div class="md:col-span-1">
                        <LimitOrderForm :symbol="symbol" @orderPlaced="refreshAll" />
                    </div>

                    <!-- Order Book -->
                    <div class="md:col-span-1">
                        <OrderBook :symbol="symbol" key="orderbook" />
                    </div>

                    <!-- My Orders -->
                    <div class="md:col-span-1 bg-white p-4 rounded shadow">
                        <h3 class="font-bold text-lg mb-4">My Orders</h3>
                        <div class="overflow-y-auto max-h-96">
                            <div v-for="order in myOrders" :key="order.id" class="border-b py-2 text-sm">
                                <div class="flex justify-between">
                                    <span :class="order.side === 'buy' ? 'text-green-600' : 'text-red-600'" class="font-bold uppercase">
                                        {{ order.side }}
                                    </span>
                                    <span class="text-gray-500">{{ new Date(order.created_at).toLocaleTimeString() }}</span>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span>{{ parseFloat(order.amount).toFixed(4) }} @ {{ parseFloat(order.price).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between mt-1 items-center">
                                    <span class="text-xs px-2 py-1 rounded bg-gray-100">
                                        {{ order.status === 1 ? 'Open' : (order.status === 2 ? 'Filled' : 'Cancelled') }}
                                    </span>
                                    <button v-if="order.status === 1" @click="cancelOrder(order.id)" class="text-red-500 text-xs hover:underline">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>
