import axios from 'axios';
import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Alpine = Alpine;
Alpine.start();

window.Pusher = Pusher;

if (window.kAgentReverb?.enabled && window.kAgentReverb?.key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: window.kAgentReverb.key,
        wsHost: window.kAgentReverb.host,
        wsPort: window.kAgentReverb.port,
        wssPort: window.kAgentReverb.port,
        forceTLS: window.kAgentReverb.forceTLS,
        enabledTransports: ['ws', 'wss'],
    });
}
