<script setup>
import { useToast } from '../composables/useToast';

const { toasts, removeToast } = useToast();

const getToastClasses = (type) => {
    const base = 'flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg backdrop-blur-sm transition-all duration-300';
    switch (type) {
        case 'success':
            return `${base} bg-green-600/90 text-white`;
        case 'error':
            return `${base} bg-red-600/90 text-white`;
        case 'warning':
            return `${base} bg-yellow-500/90 text-white`;
        default:
            return `${base} bg-slate-700/90 text-white`;
    }
};

const getIcon = (type) => {
    switch (type) {
        case 'success':
            return '✓';
        case 'error':
            return '✕';
        case 'warning':
            return '⚠';
        default:
            return 'ℹ';
    }
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="getToastClasses(toast.type)"
                >
                    <span class="text-lg font-bold">{{ getIcon(toast.type) }}</span>
                    <span class="flex-1 text-sm">{{ toast.message }}</span>
                    <button
                        @click="removeToast(toast.id)"
                        class="text-white/70 hover:text-white transition"
                    >
                        ✕
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active {
    animation: slideIn 0.3s ease-out;
}

.toast-leave-active {
    animation: slideOut 0.3s ease-in;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}
</style>

