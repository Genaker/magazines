import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const getCsrfToken = () => {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (metaToken) {
        return metaToken;
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
};

const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
    if (window.location.protocol !== 'https:' && !isLocalhost) {
        return;
    }

    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
};

window.csrfFetch = (url, options = {}) => {
    const headers = {
        'X-CSRF-TOKEN': getCsrfToken(),
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };

    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });
};

document.addEventListener('DOMContentLoaded', () => {
    registerServiceWorker();
});
