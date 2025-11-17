// ========================================
// ADMIN API CLIENT - JWT Authentication
// ========================================

class AdminAPIClient {
    constructor() {
        // Configure API base URL - new backend location
        this.baseURL = '/backend/public';
        
        // Token storage keys
        this.TOKEN_KEY = 'admin_access_token';
        this.REFRESH_TOKEN_KEY = 'admin_refresh_token';
        
        console.log('✅ Admin API Client initialized with base URL:', this.baseURL);
    }

    /**
     * Get stored access token
     */
    getToken() {
        return localStorage.getItem(this.TOKEN_KEY);
    }

    /**
     * Get stored refresh token
     */
    getRefreshToken() {
        return localStorage.getItem(this.REFRESH_TOKEN_KEY);
    }

    /**
     * Store tokens
     */
    setTokens(accessToken, refreshToken) {
        localStorage.setItem(this.TOKEN_KEY, accessToken);
        if (refreshToken) {
            localStorage.setItem(this.REFRESH_TOKEN_KEY, refreshToken);
        }
    }

    /**
     * Clear stored tokens
     */
    clearTokens() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.REFRESH_TOKEN_KEY);
    }

    /**
     * Generic fetch wrapper with JWT authentication and 401 handling
     */
    async fetch(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const token = this.getToken();
        
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        // Add Authorization header if token exists
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            // Handle 401 Unauthorized - force logout
            if (response.status === 401) {
                console.warn('⚠️ 401 Unauthorized - forcing logout');
                this.clearTokens();
                
                // Trigger logout event for AdminPanel to handle
                window.dispatchEvent(new CustomEvent('admin:unauthorized'));
                
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Сессия истекла. Пожалуйста, войдите снова.');
            }

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return { success: true, data };
        } catch (error) {
            console.error(`API Error [${endpoint}]:`, error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Login with credentials
     */
    async login(login, password) {
        const result = await this.fetch('/api/auth/login', {
            method: 'POST',
            body: JSON.stringify({ login, password })
        });

        if (result.success && result.data.data) {
            const { token, refreshToken, user } = result.data.data;
            this.setTokens(token, refreshToken);
            return { success: true, user };
        }

        return { success: false, error: result.error };
    }

    /**
     * Get current user profile (validates token)
     */
    async getCurrentUser() {
        return await this.fetch('/api/auth/me');
    }

    /**
     * Logout (optional backend call)
     */
    async logout() {
        // Call backend logout endpoint (optional, mainly for logging)
        await this.fetch('/api/auth/logout', {
            method: 'POST'
        });
        
        // Clear tokens
        this.clearTokens();
    }

    /**
     * Refresh access token
     */
    async refreshToken() {
        const refreshToken = this.getRefreshToken();
        if (!refreshToken) {
            return { success: false, error: 'No refresh token available' };
        }

        const result = await this.fetch('/api/auth/refresh', {
            method: 'POST',
            body: JSON.stringify({ refreshToken })
        });

        if (result.success && result.data.data) {
            const { token, refreshToken: newRefreshToken } = result.data.data;
            this.setTokens(token, newRefreshToken);
            return { success: true };
        }

        return { success: false, error: result.error };
    }
}
