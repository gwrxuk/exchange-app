<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { storeToRefs } from 'pinia';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();
const { confirm } = useConfirm();

const { user, isAuthenticated } = storeToRefs(authStore);
const { logout: authLogout } = authStore;

// Data
const symbols = ref([]);
const selectedSymbol = ref('BTC');
const balance = ref(0);
const assets = ref([]);
const orders = ref([]);
const orderBook = ref({ buys: [], sells: [] });

// Advanced Filters
const filters = ref({
    status: 'all',     // all, open, filled, cancelled
    symbol: 'all',     // all, BTC, ETH, etc.
    side: 'all',       // all, buy, sell
});

// Computed - Advanced Filtering
const filteredOrders = computed(() => {
    let result = orders.value;
    
    // Filter by status
    if (filters.value.status !== 'all') {
        const statusMap = { open: 1, filled: 2, cancelled: 3 };
        result = result.filter(o => o.status === statusMap[filters.value.status]);
    }
    
    // Filter by symbol
    if (filters.value.symbol !== 'all') {
        result = result.filter(o => o.symbol === filters.value.symbol);
    }
    
    // Filter by side
    if (filters.value.side !== 'all') {
        result = result.filter(o => o.side === filters.value.side);
    }
    
    return result;
});

// Stats
const openOrdersCount = computed(() => orders.value.filter(o => o.status === 1).length);
const filledOrdersCount = computed(() => orders.value.filter(o => o.status === 2).length);
const cancelledOrdersCount = computed(() => orders.value.filter(o => o.status === 3).length);

const totalVolume = computed(() => {
    return filteredOrders.value.reduce((sum, o) => sum + (o.price * o.amount), 0);
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.status !== 'all') count++;
    if (filters.value.symbol !== 'all') count++;
    if (filters.value.side !== 'all') count++;
    return count;
});

// API Calls
const fetchSymbols = async () => {
    try {
        const res = await axios.get('/api/symbols');
        symbols.value = res.data;
    } catch (e) {
        console.error('Failed to fetch symbols:', e);
    }
};

const fetchProfile = async () => {
    try {
        const res = await axios.get('/api/profile');
        balance.value = parseFloat(res.data.user?.balance) || 0;
        assets.value = res.data.assets || [];
    } catch (e) {
        if (e.response?.status === 401) {
            router.push('/login');
        }
        console.error(e);
    }
};

const fetchOrders = async () => {
    try {
        const res = await axios.get('/api/my-orders');
        orders.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

const fetchOrderBook = async () => {
    try {
        const res = await axios.get(`/api/public/orders?symbol=${selectedSymbol.value}`);
        orderBook.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

// Actions
const cancelOrder = async (id) => {
    const confirmed = await confirm('Are you sure you want to cancel this order? Your funds will be refunded.');
    if (!confirmed) return;
    
    try {
        await axios.post(`/api/orders/${id}/cancel`);
        toast.success('Order cancelled. Funds refunded.');
        fetchProfile();
        fetchOrders();
        fetchOrderBook();
    } catch (e) {
        toast.error(e.response?.data?.error || 'Failed to cancel order');
    }
};

const clearFilters = () => {
    filters.value = { status: 'all', symbol: 'all', side: 'all' };
    toast.info('Filters cleared');
};

const logout = async () => {
    await authLogout();
    router.push('/');
};

// Helpers
const getStatusText = (status) => {
    switch(status) {
        case 1: return 'Open';
        case 2: return 'Filled';
        case 3: return 'Cancelled';
        default: return 'Unknown';
    }
};

const getStatusClass = (status) => {
    switch(status) {
        case 1: return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30';
        case 2: return 'bg-green-500/20 text-green-400 border border-green-500/30';
        case 3: return 'bg-gray-500/20 text-gray-400 border border-gray-500/30';
        default: return 'bg-gray-500/20 text-gray-400';
    }
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleString();
};

const formatNumber = (num, decimals = 2) => {
    return parseFloat(num).toLocaleString('en-US', { 
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals 
    });
};

// Real-time updates
let privateChannel = null;
let publicChannel = null;

const setupEcho = () => {
    if (!window.Echo) return;
    
    // Private channel for personal order updates (OrderMatched)
    if (user.value?.id) {
        privateChannel = window.Echo.private(`App.Models.User.${user.value.id}`)
            .listen('.OrderMatched', (e) => {
                console.log('OrderMatched event received:', e);
                toast.success(`Trade executed: ${e.trade.amount} ${e.trade.symbol} @ $${e.trade.price}`);
                fetchProfile();
                fetchOrders();
                fetchOrderBook();
            });
    }
    
    // Public channel for order book updates (all users)
    publicChannel = window.Echo.channel(`orderbook.${selectedSymbol.value}`)
        .listen('.OrderBookUpdated', (e) => {
            console.log('OrderBookUpdated event received:', e);
            fetchOrderBook();
        });
};

// Watch symbol changes to update public channel subscription
watch(selectedSymbol, (newSymbol, oldSymbol) => {
    fetchOrderBook();
    if (oldSymbol && window.Echo) {
        window.Echo.leave(`orderbook.${oldSymbol}`);
        publicChannel = window.Echo.channel(`orderbook.${newSymbol}`)
            .listen('.OrderBookUpdated', (e) => {
                console.log('OrderBookUpdated event received:', e);
                fetchOrderBook();
            });
    }
});

onMounted(async () => {
    if (!isAuthenticated.value) {
        router.push('/login');
        return;
    }
    
    await fetchSymbols();
    fetchProfile();
    fetchOrders();
    fetchOrderBook();
    setupEcho();
});

onUnmounted(() => {
    if (window.Echo) {
        if (user.value?.id) {
            window.Echo.leave(`App.Models.User.${user.value.id}`);
        }
        window.Echo.leave(`orderbook.${selectedSymbol.value}`);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white">
        <!-- Navigation -->
        <nav class="bg-slate-900/80 backdrop-blur-sm border-b border-slate-700/50 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-8">
                        <span class="text-xl font-bold">
                            <span class="text-purple-400">Exchange</span>
                            <span class="text-white">Mini</span>
                        </span>
                        <div class="flex gap-1">
                            <RouterLink 
                                to="/order" 
                                class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-slate-700 transition text-sm font-medium"
                            >
                                Place Order
                            </RouterLink>
                            <RouterLink 
                                to="/wallet" 
                                class="px-4 py-2 rounded-lg bg-purple-600 text-white text-sm font-medium"
                            >
                                Wallet & Orders
                            </RouterLink>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-gray-400 text-sm">{{ user?.email }}</span>
                        <button 
                            @click="logout" 
                            class="px-4 py-2 rounded-lg border border-slate-600 hover:bg-slate-700 transition text-sm"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Title -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Orders & Wallet Overview</h1>
                <p class="text-gray-400">View your balances, assets, and order history</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- LEFT SIDEBAR: Balances & Order Book -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- USD Balance -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h3 class="text-xs font-medium text-gray-400 mb-3 uppercase tracking-wider">USD Balance</h3>
                        <div class="text-3xl font-bold font-mono text-green-400">
                            ${{ formatNumber(balance) }}
                        </div>
                    </div>

                    <!-- Asset Balances -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <h3 class="text-xs font-medium text-gray-400 mb-4 uppercase tracking-wider">Asset Balances</h3>
                        <div v-if="assets.length" class="space-y-3">
                            <div v-for="asset in assets" :key="asset.id" class="flex justify-between items-center p-3 rounded-lg bg-slate-900/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 font-bold text-sm">
                                        {{ asset.symbol.charAt(0) }}
                                    </div>
                                    <span class="font-bold">{{ asset.symbol }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono text-white">{{ formatNumber(asset.amount, 4) }}</div>
                                    <div v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-yellow-400">
                                        🔒 {{ formatNumber(asset.locked_amount, 4) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-sm text-center py-4">No assets yet</div>
                    </div>

                    <!-- Order Book -->
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700/50">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Order Book</h3>
                            <select
                                v-model="selectedSymbol"
                                @change="fetchOrderBook"
                                class="px-2 py-1 rounded bg-slate-700 border border-slate-600 text-sm text-purple-400 cursor-pointer"
                            >
                                <option v-for="s in symbols" :key="s.id" :value="s.code">{{ s.code }}</option>
                            </select>
                        </div>
                        
                        <!-- Sell Orders -->
                        <div class="mb-3">
                            <div class="text-xs text-red-400 mb-1">SELLS</div>
                            <div class="space-y-1 max-h-28 overflow-y-auto">
                                <div
                                    v-for="order in orderBook.sells?.slice().reverse().slice(0, 5)"
                                    :key="order.id"
                                    class="flex justify-between text-xs bg-red-900/20 px-2 py-1 rounded"
                                >
                                    <span class="text-red-400 font-mono">${{ formatNumber(order.price) }}</span>
                                    <span class="text-gray-400 font-mono">{{ formatNumber(order.amount, 4) }}</span>
                                </div>
                                <div v-if="!orderBook.sells?.length" class="text-gray-500 text-xs text-center py-2">—</div>
                            </div>
                        </div>

                        <div class="border-t border-slate-700 my-2"></div>

                        <!-- Buy Orders -->
                        <div>
                            <div class="text-xs text-green-400 mb-1">BUYS</div>
                            <div class="space-y-1 max-h-28 overflow-y-auto">
                                <div
                                    v-for="order in orderBook.buys?.slice(0, 5)"
                                    :key="order.id"
                                    class="flex justify-between text-xs bg-green-900/20 px-2 py-1 rounded"
                                >
                                    <span class="text-green-400 font-mono">${{ formatNumber(order.price) }}</span>
                                    <span class="text-gray-400 font-mono">{{ formatNumber(order.amount, 4) }}</span>
                                </div>
                                <div v-if="!orderBook.buys?.length" class="text-gray-500 text-xs text-center py-2">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: All Orders with Advanced Filtering -->
                <div class="lg:col-span-3">
                    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-hidden">
                        
                        <!-- Advanced Filters Bar -->
                        <div class="p-4 border-b border-slate-700/50 bg-slate-900/30">
                            <div class="flex flex-wrap items-center gap-4">
                                <!-- Status Filter -->
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-400 uppercase">Status</label>
                                    <select 
                                        v-model="filters.status"
                                        class="px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-sm cursor-pointer focus:ring-2 focus:ring-purple-500"
                                    >
                                        <option value="all">All ({{ orders.length }})</option>
                                        <option value="open">Open ({{ openOrdersCount }})</option>
                                        <option value="filled">Filled ({{ filledOrdersCount }})</option>
                                        <option value="cancelled">Cancelled ({{ cancelledOrdersCount }})</option>
                                    </select>
                                </div>

                                <!-- Symbol Filter -->
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-400 uppercase">Symbol</label>
                                    <select 
                                        v-model="filters.symbol"
                                        class="px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-sm cursor-pointer focus:ring-2 focus:ring-purple-500"
                                    >
                                        <option value="all">All Symbols</option>
                                        <option v-for="s in symbols" :key="s.id" :value="s.code">{{ s.code }}</option>
                                    </select>
                                </div>

                                <!-- Side Filter -->
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-400 uppercase">Side</label>
                                    <select 
                                        v-model="filters.side"
                                        class="px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 text-sm cursor-pointer focus:ring-2 focus:ring-purple-500"
                                    >
                                        <option value="all">All Sides</option>
                                        <option value="buy">Buy Only</option>
                                        <option value="sell">Sell Only</option>
                                    </select>
                                </div>

                                <!-- Clear Filters -->
                                <button 
                                    v-if="activeFiltersCount > 0"
                                    @click="clearFilters"
                                    class="px-3 py-2 rounded-lg text-sm text-gray-400 hover:text-white hover:bg-slate-700 transition flex items-center gap-1"
                                >
                                    <span>✕</span>
                                    Clear ({{ activeFiltersCount }})
                                </button>

                                <!-- Spacer -->
                                <div class="flex-1"></div>

                                <!-- Total Volume -->
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 uppercase">Total Volume</div>
                                    <div class="font-mono font-bold text-purple-400">${{ formatNumber(totalVolume) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Results Info -->
                        <div class="px-4 py-2 bg-slate-900/20 border-b border-slate-700/30 flex justify-between items-center">
                            <span class="text-sm text-gray-400">
                                Showing <span class="text-white font-medium">{{ filteredOrders.length }}</span> of {{ orders.length }} orders
                            </span>
                        </div>

                        <!-- Orders Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b border-slate-700/50">
                                        <th class="text-left p-4">Date</th>
                                        <th class="text-left p-4">Symbol</th>
                                        <th class="text-left p-4">Side</th>
                                        <th class="text-right p-4">Price</th>
                                        <th class="text-right p-4">Amount</th>
                                        <th class="text-right p-4">Total</th>
                                        <th class="text-center p-4">Status</th>
                                        <th class="text-center p-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="order in filteredOrders" 
                                        :key="order.id"
                                        class="border-b border-slate-700/30 hover:bg-slate-700/20 transition"
                                    >
                                        <td class="p-4 text-sm text-gray-400">{{ formatDate(order.created_at) }}</td>
                                        <td class="p-4">
                                            <span class="font-bold text-purple-400">{{ order.symbol }}</span>
                                        </td>
                                        <td class="p-4">
                                            <span :class="order.side === 'buy' ? 'text-green-400' : 'text-red-400'" class="font-bold uppercase">
                                                {{ order.side }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-right font-mono">${{ formatNumber(order.price) }}</td>
                                        <td class="p-4 text-right font-mono">{{ formatNumber(order.amount, 4) }}</td>
                                        <td class="p-4 text-right font-mono text-gray-300">
                                            ${{ formatNumber(order.price * order.amount) }}
                                        </td>
                                        <td class="p-4 text-center">
                                            <span :class="getStatusClass(order.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                                {{ getStatusText(order.status) }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button
                                                v-if="order.status === 1"
                                                @click="cancelOrder(order.id)"
                                                class="px-3 py-1 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 text-sm font-medium transition"
                                            >
                                                Cancel
                                            </button>
                                            <span v-else class="text-gray-600">—</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!filteredOrders.length">
                                        <td colspan="8" class="p-12 text-center text-gray-500">
                                            <div class="text-4xl mb-2">📋</div>
                                            <div v-if="activeFiltersCount > 0">
                                                No orders match your filters
                                                <button @click="clearFilters" class="text-purple-400 hover:underline ml-1">Clear filters</button>
                                            </div>
                                            <div v-else>No orders yet</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
