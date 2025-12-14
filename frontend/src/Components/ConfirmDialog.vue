<script setup>
import { useConfirm } from '../composables/useConfirm';

const { isOpen, message, handleConfirm, handleCancel } = useConfirm();
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                    @click="handleCancel"
                ></div>
                
                <!-- Dialog -->
                <div class="relative bg-slate-800 rounded-xl p-6 shadow-2xl max-w-md mx-4 border border-slate-700">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center">
                            <span class="text-yellow-400 text-xl">⚠</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white mb-2">Confirm Action</h3>
                            <p class="text-gray-400">{{ message }}</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            @click="handleCancel"
                            class="px-4 py-2 rounded-lg border border-slate-600 text-gray-400 hover:text-white hover:bg-slate-700 transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="handleConfirm"
                            class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white font-medium transition"
                        >
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

