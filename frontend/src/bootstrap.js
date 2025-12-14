import axios from 'axios';

// Configure axios
axios.defaults.baseURL = '';  // Same origin
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Set token from localStorage if exists
const storedToken = localStorage.getItem('token');
if (storedToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`;
}

window.axios = axios;

// Pusher/Echo for real-time with JWT auth
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Function to create/recreate Echo instance with current token
function createEchoInstance(token) {
    return new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        forceTLS: true,
        authEndpoint: '/api/broadcasting/auth',
        auth: {
            headers: {
                Authorization: token ? `Bearer ${token}` : '',
                Accept: 'application/json',
            },
        },
    });
}

// Initialize Echo with stored token
window.Echo = createEchoInstance(storedToken);

// Function to reinitialize Echo with new token
window.updateEchoAuth = function(newToken) {
    // Disconnect existing Echo
    if (window.Echo) {
        window.Echo.disconnect();
    }
    
    // Create new Echo instance with updated token
    window.Echo = createEchoInstance(newToken);
    
    console.log('Echo reinitialized with new token');
};
