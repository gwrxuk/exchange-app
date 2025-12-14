<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { storeToRefs } from 'pinia';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const { user, isAuthenticated } = storeToRefs(authStore);
const { logout: authLogout } = authStore;

// Commission rate (1.5%)
const COMMISSION_RATE = 0.015;

// Data
const symbols = ref([]);
const symbol = ref('BTC');
const orderBook = ref({ buys: [], sells: [] });
const balance = ref(0);
const assets = ref([]);

// Order Form
const orderForm = ref({
    side: 'buy',
    price: '',
    amount: '',
});
const orderErrors = ref({});
const orderProcessing = ref(false);

// Computed - Volume Calculation Preview
const subtotal = computed(() => {
    const price = parseFloat(orderForm.value.price) || 0;
    const amount = parseFloat(orderForm.value.amount) || 0;
    return price * amount;
});

const commission = computed(() => {
    return subtotal.value * COMMISSION_RATE;
});

const totalCost = computed(() => {
    if (orderForm.value.side === 'buy') {
        return subtotal.value + commission.value;
    }
    return subtotal.value;
});

const totalReceive = computed(() => {
    if (orderForm.value.side === 'sell') {
        return subtotal.value - commission.value;
    }
    return 0;
});

const selectedAsset = computed(() => {
    return assets.value.find(a => a.symbol === symbol.value);
});

const availableBalance = computed(() => {
    if (orderForm.value.side === 'buy') {
        return balance.value;
    }
    return selectedAsset.value?.amount || 0;
});

const insufficientFunds = computed(() => {
    if (orderForm.value.side === 'buy') {
        return totalCost.value > balance.value && subtotal.value > 0;
    }
    const amount = parseFloat(orderForm.value.amount) || 0;
    return amount > (selectedAsset.value?.amount || 0) && amount > 0;
});

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

// Actions
const placeOrder = async () => {
    if (insufficientFunds.value) {
        toast.error('Insufficient funds for this order');
        return;
    }
    
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
        
        fetchOrderBook();
        fetchProfile();
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

const setMaxAmount = () => {
    if (orderForm.value.side === 'sell' && selectedAsset.value) {
        orderForm.value.amount = selectedAsset.value.amount;
    } else if (orderForm.value.side === 'buy' && orderForm.value.price) {
        // Calculate max amount based on available balance (accounting for commission)
        const price = parseFloat(orderForm.value.price);
        const maxAmount = balance.value / (price * (1 + COMMISSION_RATE));
        orderForm.value.amount = Math.floor(maxAmount * 10000) / 10000; // Floor to 4 decimals
    }
};

const logout = async () => {
    await authLogout();
    router.push('/');
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
                fetchOrderBook();
            });
    }
    
    // Public channel for order book updates (all users)
    publicChannel = window.Echo.channel(`orderbook.${symbol.value}`)
        .listen('.OrderBookUpdated', (e) => {
            console.log('OrderBookUpdated event received:', e);
            fetchOrderBook();
        });
};

const updatePublicChannel = () => {
    // Leave old channel and join new one when symbol changes
    if (publicChannel && window.Echo) {
        window.Echo.leave(`orderbook.${symbol.value}`);
    }
    if (window.Echo) {
        publicChannel = window.Echo.channel(`orderbook.${symbol.value}`)
            .listen('.OrderBookUpdated', (e) => {
                console.log('OrderBookUpdated event received:', e);
                fetchOrderBook();
            });
    }
};

// Watch symbol changes to update public channel subscription
watch(symbol, (newSymbol, oldSymbol) => {
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
    fetchOrderBook();
    fetchProfile();
    setupEcho();
});

onUnmounted(() => {
    if (window.Echo) {
        if (user.value?.id) {
            window.Echo.leave(`App.Models.User.${user.value.id}`);
        }
        window.Echo.leave(`orderbook.${symbol.value}`);
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
                                class="px-4 py-2 rounded-lg bg-purple-600 text-white text-sm font-medium"
                            >
                                Place Order
                            </RouterLink>
                            <RouterLink 
                                to="/wallet" 
                                class="px-4 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-slate-700 transition text-sm font-medium"
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

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Page Title -->
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold mb-2">Limit Order Form</h1>
                <p class="text-gray-400">Place buy or sell orders at your desired price</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                <!-- Order Form -->
                <div class="lg:col-span-3 bg-slate-800/50 backdrop-blur-sm rounded-2xl p-8 border border-slate-700/50">
                    <form @submit.prevent="placeOrder" class="space-y-6">
                        <!-- Available Balance Banner -->
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-700 flex justify-between items-center">
                            <span class="text-gray-400 text-sm">Available</span>
                            <span class="font-mono font-bold" :class="orderForm.side === 'buy' ? 'text-green-400' : 'text-purple-400'">
                                <template v-if="orderForm.side === 'buy'">${{ formatNumber(balance) }} USD</template>
                                <template v-else>{{ formatNumber(selectedAsset?.amount || 0, 4) }} {{ symbol }}</template>
                            </span>
                        </div>

                        <!-- Symbol Dropdown -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Symbol</label>
                            <select
                                v-model="symbol"
                                class="w-full px-4 py-4 rounded-xl bg-slate-700/50 border border-slate-600 text-white text-lg focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer"
                            >
                                <option v-for="s in symbols" :key="s.id" :value="s.code" class="bg-slate-800">
                                    {{ s.code }} - {{ s.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Side (Buy/Sell) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Side</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="orderForm.side = 'buy'"
                                    :class="[
                                        'py-4 rounded-xl font-bold text-lg transition',
                                        orderForm.side === 'buy' 
                                            ? 'bg-green-600 text-white shadow-lg shadow-green-600/30' 
                                            : 'bg-slate-700/50 text-gray-400 hover:text-white border border-slate-600'
                                    ]"
                                >
                                    BUY
                                </button>
                                <button
                                    type="button"
                                    @click="orderForm.side = 'sell'"
                                    :class="[
                                        'py-4 rounded-xl font-bold text-lg transition',
                                        orderForm.side === 'sell' 
                                            ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' 
                                            : 'bg-slate-700/50 text-gray-400 hover:text-white border border-slate-600'
                                    ]"
                                >
                                    SELL
                                </button>
                            </div>
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Price (USD)</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="orderForm.price"
                                class="w-full px-4 py-4 rounded-xl bg-slate-700/50 border border-slate-600 text-white text-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="0.00"
                                required
                            />
                        </div>

                        <!-- Amount -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-medium text-gray-300">Amount ({{ symbol }})</label>
                                <button 
                                    type="button" 
                                    @click="setMaxAmount"
                                    class="text-xs text-purple-400 hover:text-purple-300 font-medium"
                                >
                                    MAX
                                </button>
                            </div>
                            <input
                                type="number"
                                step="0.0001"
                                v-model="orderForm.amount"
                                class="w-full px-4 py-4 rounded-xl bg-slate-700/50 border border-slate-600 text-white text-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="0.0000"
                                required
                            />
                        </div>

                        <!-- Volume Calculation Preview -->
                        <div class="p-5 rounded-xl bg-slate-900/70 border border-slate-700 space-y-3">
                            <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Order Preview</div>
                            
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-400">Subtotal</span>
                                <span class="font-mono text-white">${{ formatNumber(subtotal) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-400">
                                    Commission (1.5%)
                                    <span class="text-xs text-gray-500 ml-1">{{ orderForm.side === 'buy' ? 'added' : 'deducted' }}</span>
                                </span>
                                <span class="font-mono text-yellow-400">${{ formatNumber(commission) }}</span>
                            </div>
                            
                            <div class="border-t border-slate-700 pt-3"></div>
                            
                            <div v-if="orderForm.side === 'buy'" class="flex justify-between items-center">
                                <span class="text-gray-300 font-medium">Total Cost</span>
                                <span class="font-mono text-xl font-bold text-green-400">${{ formatNumber(totalCost) }}</span>
                            </div>
                            
                            <div v-else class="flex justify-between items-center">
                                <span class="text-gray-300 font-medium">You Receive</span>
                                <span class="font-mono text-xl font-bold text-red-400">${{ formatNumber(totalReceive) }}</span>
                            </div>

                            <!-- Insufficient Funds Warning -->
                            <div v-if="insufficientFunds" class="mt-3 p-3 rounded-lg bg-red-500/10 border border-red-500/30 flex items-center gap-2">
                                <span class="text-red-400">⚠️</span>
                                <span class="text-red-400 text-sm">Insufficient {{ orderForm.side === 'buy' ? 'USD balance' : symbol + ' balance' }}</span>
                            </div>
                        </div>

                        <!-- Error Display -->
                        <div v-if="Object.keys(orderErrors).length" class="p-4 rounded-xl bg-red-500/10 border border-red-500/30">
                            <p v-for="(errors, field) in orderErrors" :key="field" class="text-red-400 text-sm">
                                {{ errors[0] }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="orderProcessing || insufficientFunds"
                            :class="[
                                'w-full py-4 rounded-xl font-bold text-lg transition disabled:opacity-50 shadow-lg',
                                orderForm.side === 'buy'
                                    ? 'bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 shadow-green-600/30'
                                    : 'bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 shadow-red-600/30'
                            ]"
                        >
                            {{ orderProcessing ? 'Processing...' : `Place ${orderForm.side.toUpperCase()} Order` }}
                        </button>
                    </form>
                </div>

                <!-- Order Book -->
                <div class="lg:col-span-2 bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700/50">
                    <h3 class="text-lg font-bold mb-6">
                        Order Book 
                        <span class="text-purple-400">({{ symbol }})</span>
                    </h3>
                    
                    <!-- Sell Orders -->
                    <div class="mb-6">
                        <div class="text-xs text-red-400 font-medium mb-2 uppercase tracking-wider">Sell Orders</div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs text-gray-500 px-3 py-1">
                                <span>Price</span>
                                <span>Amount</span>
                                <span>Total</span>
                            </div>
                            <div
                                v-for="order in orderBook.sells?.slice().reverse().slice(0, 8)"
                                :key="order.id"
                                class="flex justify-between text-sm bg-red-900/20 px-3 py-2 rounded-lg"
                            >
                                <span class="text-red-400 font-mono">${{ formatNumber(order.price) }}</span>
                                <span class="text-gray-300 font-mono">{{ formatNumber(order.amount, 4) }}</span>
                                <span class="text-gray-500 font-mono text-xs">${{ formatNumber(order.price * order.amount) }}</span>
                            </div>
                            <div v-if="!orderBook.sells?.length" class="text-gray-500 text-sm text-center py-4">
                                No sell orders
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-700 my-4"></div>

                    <!-- Buy Orders -->
                    <div>
                        <div class="text-xs text-green-400 font-medium mb-2 uppercase tracking-wider">Buy Orders</div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs text-gray-500 px-3 py-1">
                                <span>Price</span>
                                <span>Amount</span>
                                <span>Total</span>
                            </div>
                            <div
                                v-for="order in orderBook.buys?.slice(0, 8)"
                                :key="order.id"
                                class="flex justify-between text-sm bg-green-900/20 px-3 py-2 rounded-lg"
                            >
                                <span class="text-green-400 font-mono">${{ formatNumber(order.price) }}</span>
                                <span class="text-gray-300 font-mono">{{ formatNumber(order.amount, 4) }}</span>
                                <span class="text-gray-500 font-mono text-xs">${{ formatNumber(order.price * order.amount) }}</span>
                            </div>
                            <div v-if="!orderBook.buys?.length" class="text-gray-500 text-sm text-center py-4">
                                No buy orders
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
