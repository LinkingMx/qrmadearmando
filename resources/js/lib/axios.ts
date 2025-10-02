import axios from 'axios';
import { router } from '@inertiajs/react';

// Set base configuration
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Get CSRF token from meta tag or cookie
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
} else {
    // Fallback: Get from cookie
    const csrfCookie = document.cookie
        .split(';')
        .find(row => row.trim().startsWith('XSRF-TOKEN='));
    if (csrfCookie) {
        const csrfToken = decodeURIComponent(csrfCookie.split('=')[1]);
        axios.defaults.headers.common['X-XSRF-TOKEN'] = csrfToken;
    }
}

// Request interceptor
axios.interceptors.request.use(
    (config) => {
        // Ensure we have the latest CSRF token from cookie
        const csrfCookie = document.cookie
            .split(';')
            .find(row => row.trim().startsWith('XSRF-TOKEN='));
        if (csrfCookie) {
            const csrfToken = decodeURIComponent(csrfCookie.split('=')[1]);
            config.headers['X-XSRF-TOKEN'] = csrfToken;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor
axios.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        // Handle 419 CSRF token mismatch
        if (error.response?.status === 419) {
            // Refresh the page to get a new CSRF token
            window.location.reload();
        }

        // Handle 401 Unauthenticated
        if (error.response?.status === 401) {
            // Redirect to login using Inertia
            router.visit('/login');
        }

        // Handle 403 Forbidden
        if (error.response?.status === 403) {
            // Show error or redirect as needed
            console.error('Access denied:', error.response.data);
        }

        return Promise.reject(error);
    }
);

export default axios;