const getCsrfToken = () => {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (metaToken) {
        return metaToken;
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
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
});
