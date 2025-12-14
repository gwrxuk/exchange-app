<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    symbol: String
});

const emit = defineEmits(['orderPlaced']);

const form = ref({
    symbol: props.symbol,
    side: 'buy',
    price: '',
    amount: ''
});

const message = ref('');
const error = ref('');

const submitOrder = async () => {
    message.value = '';
    error.value = '';
    
    try {
        await axios.post('/api/orders', {
            symbol: form.value.symbol,
            side: form.value.side,
            price: form.value.price,
            amount: form.value.amount
        });
        
        message.value = 'Order placed successfully!';
        form.value.price = '';
        form.value.amount = '';
        emit('orderPlaced');
    } catch (err) {
        error.value = err.response?.data?.error || err.message;
    }
};
</script>

<template>
    <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold text-lg mb-4">Place Order</h3>
        <form @submit.prevent="submitOrder" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Side</label>
                <select v-model="form.side" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="buy">Buy</option>
                    <option value="sell">Sell</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Price (USD)</label>
                <input type="number" step="0.01" v-model="form.price" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Amount ({{ symbol }})</label>
                <input type="number" step="0.0001" v-model="form.amount" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                Place {{ form.side.toUpperCase() }} Order
            </button>
            
            <div v-if="message" class="text-green-600 text-sm">{{ message }}</div>
            <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
        </form>
    </div>
</template>

