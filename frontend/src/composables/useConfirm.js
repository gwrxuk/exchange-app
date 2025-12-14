import { ref } from 'vue';

const isOpen = ref(false);
const message = ref('');
const resolvePromise = ref(null);

export function useConfirm() {
    const confirm = (msg) => {
        message.value = msg;
        isOpen.value = true;
        
        return new Promise((resolve) => {
            resolvePromise.value = resolve;
        });
    };

    const handleConfirm = () => {
        isOpen.value = false;
        if (resolvePromise.value) {
            resolvePromise.value(true);
            resolvePromise.value = null;
        }
    };

    const handleCancel = () => {
        isOpen.value = false;
        if (resolvePromise.value) {
            resolvePromise.value(false);
            resolvePromise.value = null;
        }
    };

    return {
        isOpen,
        message,
        confirm,
        handleConfirm,
        handleCancel,
    };
}

