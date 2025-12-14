<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    symbol: String
});

const buys = ref([]);
const sells = ref([]);

const fetchOrderBook = async () => {
    try {
        const response = await axios.get('/api/orders', { params: { symbol: props.symbol } });
        const orders = response.data;
        
        sells.value = orders.filter(o => o.side === 'sell').sort((a, b) => a.price - b.price);
        buys.value = orders.filter(o => o.side === 'buy').sort((a, b) => b.price - a.price);
    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    fetchOrderBook();

    // Listen for market updates (if public channel exists)
    // Echo.channel(`market.${props.symbol}`)
    //     .listen('OrderMatched', (e) => {
    //         fetchOrderBook();
    //     });
    
    // Ideally we should listen for OrderPlaced, OrderMatched, OrderCancelled events to update in real-time.
    // For now, I'll just refresh periodically or on OrderMatched if I add that event.
    
    // The requirement says "Listen for OrderMatched event and: patch new trade into UI... update order status in list".
    // OrderBook also needs to update.
});
</script>

<template>
    <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold text-lg mb-4">Order Book ({{ symbol }})</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <h4 class="font-semibold text-red-500">Sells</h4>
                <ul>
                    <li v-for="order in sells" :key="order.id" class="flex justify-between text-sm">
                        <span>{{ parseFloat(order.price).toFixed(2) }}</span>
                        <span>{{ parseFloat(order.remaining_amount).toFixed(4) }}</span>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold text-green-500">Buys</h4>
                <ul>
                    <li v-for="order in buys" :key="order.id" class="flex justify-between text-sm">
                        <span>{{ parseFloat(order.price).toFixed(2) }}</span>
                        <span>{{ parseFloat(order.remaining_amount).toFixed(4) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

