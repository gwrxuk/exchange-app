<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
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
const symbol = ref('BTC');
const balance = ref(0);
const assets = ref([]);
const orders = ref([]);
const orderBook = ref({ buys: [], sells: [] });
const trades = ref([]);

// Order Form
const orderForm = ref({
    side: 'buy',
    price: '',
    amount: '',
});
const orderErrors = ref({});
const orderProcessing = ref(false);

// Filter for orders view
const orderFilter = ref('all'); // all, open, filled, cancelled

let echoChannel = null;

// Computed
const filteredOrders = computed(() => {
    if (orderFilter.value === 'all') return orders.value;
    const statusMap = { open: 1, filled: 2, cancelled: 3 };
    return orders.value.filter(o => o.status === statusMap[orderFilter.value]);
});

const openOrdersCount = computed(() => orders.value.filter(o => o.status === 1).length);
const filledOrdersCount = computed(() => orders.value.filter(o => o.status === 2).length);
const cancelledOrdersCount = computed(() => orders.value.filter(o => o.status === 3).length);

// API Calls
const fetchSymbols = async () => {
    try {
        const res = await axios.get('/api/symbols');
        symbols.value = res.data;
        if (symbols.value.length > 0 && !symbols.value.find(s => s.code === symbol.value)) {
            symbol.value = symbols.value[0].code;
        }
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
        const res = await axios.get(`/api/public/orders?symbol=${symbol.value}`);
        orderBook.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

// Watch symbol changes
watch(symbol, () => {
    fetchOrderBook();
});

// Actions
const placeOrder = async () => {
    orderProcessing.value = true;
    orderErrors.value = {};
    
    try {
        const response = await axios.post('/api/orders', {
            symbol: symbol.value,
            side: orderForm.value.side,
            price: parseFloat(orderForm.value.price),
            amount: parseFloat(orderForm.value.amount),
        });
        
        const side = orderForm.value.side;
        const orderStatus = response.data.status;
        
        orderForm.value.price = '';
        orderForm.value.amount = '';
        
        if (orderStatus === 2) {
            toast.success(`${side.toUpperCase()} order matched and filled!`);
        } else {
            toast.success(`${side.toUpperCase()} order placed successfully!`);
        }
        
        fetchProfile();
        fetchOrders();
        fetchOrderBook();
    } catch (e) {
        if (e.response?.data?.errors) {
            orderErrors.value = e.response.data.errors;
            const firstError = Object.values(e.response.data.errors)[0][0];
            toast.error(firstError);
        } else if (e.response?.data?.error) {
            orderErrors.value = { general: [e.response.data.error] };
            toast.error(e.response.data.error);
        } else {
            orderErrors.value = { general: ['Failed to place order'] };
            toast.error('Failed to place order');
        }
    } finally {
        orderProcessing.value = false;
    }
};

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
const setupEcho = () => {
    if (window.Echo && user.value?.id) {
        echoChannel = window.Echo.private(`App.Models.User.${user.value.id}`)
            .listen('OrderMatched', (e) => {
                console.log('OrderMatched event:', e);
                toast.info('Order matched! Updating...');
                
                // Update UI
                fetchProfile();
                fetchOrders();
                fetchOrderBook();
                
                // Add to trades list
                if (e.trade) {
                    trades.value.unshift(e.trade);
                }
            });
    }
};

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
    if (echoChannel && window.Echo && user.value?.id) {
        window.Echo.leave(`App.Models.User.${user.value.id}`);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white">
        <!-- Navigation -->
        <nav class="bg-slate-900/80 backdrop-blur-sm border-b border-slate-700/50 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-xl font-bold">
                            <span class="text-purple-400">Exchange</span>
                            <span class="text-white">Mini</span>
                        </span>
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
            
            <!-- ============================================ -->
            <!-- SECTION A: LIMIT ORDER FORM                  -->
            <!-- ============================================ -->
            <section class="mb-8">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">A</span>
                    Limit Order Form
                </h2>
                
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700/50">
                    <form @submit.prevent="placeOrder" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                        <!-- Symbol Dropdown -->
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Symbol</label>
                            <select
                                v-model="symbol"
                                class="w-full px-4 py-3 rounded-lg bg-slate-700/50 border border-slate-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer"
                            >
                                <option v-for="s in symbols" :key="s.id" :value="s.code" class="bg-slate-800">
                                    {{ s.code }}
                                </option>
                            </select>
                        </div>

                        <!-- Side (Buy/Sell) -->
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Side</label>
                            <div class="flex bg-slate-700/50 rounded-lg p-1 border border-slate-600">
                                <button
                                    type="button"
                                    @click="orderForm.side = 'buy'"
                                    :class="[
                                        'flex-1 py-2 rounded-md font-semibold transition text-sm',
                                        orderForm.side === 'buy' 
                                            ? 'bg-green-600 text-white shadow-lg shadow-green-600/30' 
                                            : 'text-gray-400 hover:text-white'
                                    ]"
                                >
                                    Buy
                                </button>
                                <button
                                    type="button"
                                    @click="orderForm.side = 'sell'"
                                    :class="[
                                        'flex-1 py-2 rounded-md font-semibold transition text-sm',
                                        orderForm.side === 'sell' 
                                            ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' 
                                            : 'text-gray-400 hover:text-white'
                                    ]"
                                >
                                    Sell
                                </button>
                            </div>
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Price (USD)</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="orderForm.price"
                                class="w-full px-4 py-3 rounded-lg bg-slate-700/50 border border-slate-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="0.00"
                                required
                            />
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Amount</label>
                            <input
                                type="number"
                                step="0.0001"
                                v-model="orderForm.amount"
                                class="w-full px-4 py-3 rounded-lg bg-slate-700/50 border border-slate-600 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="0.0000"
                                required
                            />
                        </div>

                        <!-- Total (calculated) -->
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Total</label>
                            <div class="px-4 py-3 rounded-lg bg-slate-900/50 border border-slate-700 text-gray-300 font-mono">
                                ${{ orderForm.price && orderForm.amount ? formatNumber(orderForm.price * orderForm.amount) : '0.00' }}
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button
                                type="submit"
                                :disabled="orderProcessing"
                                :class="[
                                    'w-full py-3 rounded-lg font-bold transition disabled:opacity-50 shadow-lg',
                                    orderForm.side === 'buy'
                                        ? 'bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 shadow-green-600/30'
                                        : 'bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 shadow-red-600/30'
                                ]"
                            >
                                {{ orderProcessing ? 'Processing...' : 'Place Order' }}
                            </button>
                        </div>
                    </form>
                    
                    <!-- Error Display -->
                    <div v-if="Object.keys(orderErrors).length" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30">
                        <p v-for="(errors, field) in orderErrors" :key="field" class="text-red-400 text-sm">
                            {{ errors[0] }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- SECTION B: ORDERS & WALLET OVERVIEW          -->
            <!-- ============================================ -->
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">B</span>
                    Orders & Wallet Overview
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- LEFT: Wallet Balances -->
                    <div class="lg:col-span-1 space-y-4">
                        <!-- USD Balance -->
                        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50">
                            <h3 class="text-sm font-medium text-gray-400 mb-3 uppercase tracking-wider">USD Balance</h3>
                            <div class="text-3xl font-bold font-mono text-green-400">
                                ${{ formatNumber(balance) }}
                            </div>
                        </div>

                        <!-- Asset Balances -->
                        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50">
                            <h3 class="text-sm font-medium text-gray-400 mb-3 uppercase tracking-wider">Asset Balances</h3>
                            <div v-if="assets.length" class="space-y-3">
                                <div v-for="asset in assets" :key="asset.id" class="flex justify-between items-center p-3 rounded-lg bg-slate-900/50">
                                    <div>
                                        <span class="font-bold text-purple-400">{{ asset.symbol }}</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-mono text-white">{{ formatNumber(asset.amount, 4) }}</div>
                                        <div v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-yellow-400">
                                            🔒 {{ formatNumber(asset.locked_amount, 4) }} locked
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 text-sm">No assets yet</div>
                        </div>

                        <!-- Order Book -->
                        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50">
                            <h3 class="text-sm font-medium text-gray-400 mb-3 uppercase tracking-wider">
                                Order Book <span class="text-purple-400">({{ symbol }})</span>
                            </h3>
                            
                            <!-- Sell Orders -->
                            <div class="mb-3">
                                <div class="text-xs text-red-400 mb-1">SELL ORDERS</div>
                                <div class="space-y-1 max-h-32 overflow-y-auto">
                                    <div
                                        v-for="order in orderBook.sells?.slice().reverse().slice(0, 5)"
                                        :key="order.id"
                                        class="flex justify-between text-xs bg-red-900/20 px-2 py-1 rounded"
                                    >
                                        <span class="text-red-400 font-mono">${{ formatNumber(order.price) }}</span>
                                        <span class="text-gray-400 font-mono">{{ formatNumber(order.amount, 4) }}</span>
                                    </div>
                                    <div v-if="!orderBook.sells?.length" class="text-gray-500 text-xs text-center py-2">
                                        No sell orders
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-700 my-2"></div>

                            <!-- Buy Orders -->
                            <div>
                                <div class="text-xs text-green-400 mb-1">BUY ORDERS</div>
                                <div class="space-y-1 max-h-32 overflow-y-auto">
                                    <div
                                        v-for="order in orderBook.buys?.slice(0, 5)"
                                        :key="order.id"
                                        class="flex justify-between text-xs bg-green-900/20 px-2 py-1 rounded"
                                    >
                                        <span class="text-green-400 font-mono">${{ formatNumber(order.price) }}</span>
                                        <span class="text-gray-400 font-mono">{{ formatNumber(order.amount, 4) }}</span>
                                    </div>
                                    <div v-if="!orderBook.buys?.length" class="text-gray-500 text-xs text-center py-2">
                                        No buy orders
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: All Orders -->
                    <div class="lg:col-span-2">
                        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-hidden">
                            <!-- Order Filters -->
                            <div class="flex border-b border-slate-700/50">
                                <button
                                    @click="orderFilter = 'all'"
                                    :class="[
                                        'flex-1 py-3 text-sm font-medium transition',
                                        orderFilter === 'all' 
                                            ? 'bg-slate-700/50 text-white' 
                                            : 'text-gray-400 hover:text-white hover:bg-slate-700/30'
                                    ]"
                                >
                                    All ({{ orders.length }})
                                </button>
                                <button
                                    @click="orderFilter = 'open'"
                                    :class="[
                                        'flex-1 py-3 text-sm font-medium transition',
                                        orderFilter === 'open' 
                                            ? 'bg-slate-700/50 text-yellow-400' 
                                            : 'text-gray-400 hover:text-white hover:bg-slate-700/30'
                                    ]"
                                >
                                    Open ({{ openOrdersCount }})
                                </button>
                                <button
                                    @click="orderFilter = 'filled'"
                                    :class="[
                                        'flex-1 py-3 text-sm font-medium transition',
                                        orderFilter === 'filled' 
                                            ? 'bg-slate-700/50 text-green-400' 
                                            : 'text-gray-400 hover:text-white hover:bg-slate-700/30'
                                    ]"
                                >
                                    Filled ({{ filledOrdersCount }})
                                </button>
                                <button
                                    @click="orderFilter = 'cancelled'"
                                    :class="[
                                        'flex-1 py-3 text-sm font-medium transition',
                                        orderFilter === 'cancelled' 
                                            ? 'bg-slate-700/50 text-gray-400' 
                                            : 'text-gray-400 hover:text-white hover:bg-slate-700/30'
                                    ]"
                                >
                                    Cancelled ({{ cancelledOrdersCount }})
                                </button>
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
                                                <span :class="getStatusClass(order.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                                    {{ getStatusText(order.status) }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-center">
                                                <button
                                                    v-if="order.status === 1"
                                                    @click="cancelOrder(order.id)"
                                                    class="text-red-400 hover:text-red-300 text-sm font-medium transition"
                                                >
                                                    Cancel
                                                </button>
                                                <span v-else class="text-gray-600">—</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!filteredOrders.length">
                                            <td colspan="8" class="p-8 text-center text-gray-500">
                                                No orders found
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>
</template>
