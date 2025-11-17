// ========================================
// API CLIENT - Backend Integration
// ========================================

class APIClient {
    constructor() {
        // Configure API base URL - new backend location
        this.baseURL = '/backend/public';
        
        // In-memory cache for API responses
        this.cache = {
            services: null,
            portfolio: null,
            testimonials: null,
            faq: null,
            content: null,
            stats: null,
            settings: null
        };
        
        // Loading states
        this.loading = {};
        
        // Error states
        this.errors = {};
        
        console.log('✅ API Client initialized with base URL:', this.baseURL);
    }

    /**
     * Generic fetch wrapper with error handling
     */
    async fetch(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

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
     * Get services from API (with caching)
     */
    async getServices(forceRefresh = false) {
        if (this.cache.services && !forceRefresh) {
            return { success: true, data: this.cache.services };
        }

        this.loading.services = true;
        const result = await this.fetch('/api/services');
        this.loading.services = false;

        if (result.success) {
            // API returns { success: true, data: [...] }
            this.cache.services = result.data.data || [];
            this.errors.services = null;
        } else {
            this.errors.services = result.error;
        }

        return { success: result.success, data: this.cache.services || [] };
    }

    /**
     * Get portfolio items from API (with caching)
     */
    async getPortfolio(forceRefresh = false, category = null) {
        if (this.cache.portfolio && !forceRefresh && !category) {
            return { success: true, data: this.cache.portfolio };
        }

        this.loading.portfolio = true;
        const endpoint = category ? `/api/portfolio?category=${category}` : '/api/portfolio';
        const result = await this.fetch(endpoint);
        this.loading.portfolio = false;

        if (result.success) {
            const portfolioData = result.data.data || [];
            if (!category) {
                this.cache.portfolio = portfolioData;
            }
            this.errors.portfolio = null;
            return { success: true, data: portfolioData };
        } else {
            this.errors.portfolio = result.error;
            return { success: false, data: this.cache.portfolio || [] };
        }
    }

    /**
     * Get testimonials from API (with caching)
     */
    async getTestimonials(forceRefresh = false) {
        if (this.cache.testimonials && !forceRefresh) {
            return { success: true, data: this.cache.testimonials };
        }

        this.loading.testimonials = true;
        const result = await this.fetch('/api/testimonials');
        this.loading.testimonials = false;

        if (result.success) {
            this.cache.testimonials = result.data.data || [];
            this.errors.testimonials = null;
        } else {
            this.errors.testimonials = result.error;
        }

        return { success: result.success, data: this.cache.testimonials || [] };
    }

    /**
     * Get FAQ items from API (with caching)
     */
    async getFAQ(forceRefresh = false) {
        if (this.cache.faq && !forceRefresh) {
            return { success: true, data: this.cache.faq };
        }

        this.loading.faq = true;
        const result = await this.fetch('/api/faq');
        this.loading.faq = false;

        if (result.success) {
            this.cache.faq = result.data.data || [];
            this.errors.faq = null;
        } else {
            this.errors.faq = result.error;
        }

        return { success: result.success, data: this.cache.faq || [] };
    }

    /**
     * Get content sections from API (with caching)
     */
    async getContent(forceRefresh = false) {
        if (this.cache.content && !forceRefresh) {
            return { success: true, data: this.cache.content };
        }

        this.loading.content = true;
        const result = await this.fetch('/api/content');
        this.loading.content = false;

        if (result.success) {
            // Convert array of sections to object keyed by section_key
            const contentArray = result.data.data || [];
            const contentObject = {};
            contentArray.forEach(section => {
                contentObject[section.section_key] = section.content;
            });
            this.cache.content = contentObject;
            this.errors.content = null;
        } else {
            this.errors.content = result.error;
        }

        return { success: result.success, data: this.cache.content || {} };
    }

    /**
     * Get site statistics from API (with caching)
     */
    async getStats(forceRefresh = false) {
        if (this.cache.stats && !forceRefresh) {
            return { success: true, data: this.cache.stats };
        }

        this.loading.stats = true;
        const result = await this.fetch('/api/stats');
        this.loading.stats = false;

        if (result.success) {
            this.cache.stats = result.data.data || {};
            this.errors.stats = null;
        } else {
            this.errors.stats = result.error;
        }

        return { success: result.success, data: this.cache.stats || {} };
    }

    /**
     * Get public settings from API (with caching)
     */
    async getSettings(forceRefresh = false) {
        if (this.cache.settings && !forceRefresh) {
            return { success: true, data: this.cache.settings };
        }

        this.loading.settings = true;
        const result = await this.fetch('/api/settings/public');
        this.loading.settings = false;

        if (result.success) {
            this.cache.settings = result.data.data || {};
            this.errors.settings = null;
        } else {
            this.errors.settings = result.error;
        }

        return { success: result.success, data: this.cache.settings || {} };
    }

    /**
     * Submit order/contact form to API
     */
    async submitOrder(orderData) {
        this.loading.submitOrder = true;
        
        // Map frontend field names to backend expected names
        const payload = {
            client_name: orderData.name || orderData.client_name,
            client_email: orderData.email || orderData.client_email,
            client_phone: orderData.phone || orderData.client_phone,
            telegram: orderData.telegram || '',
            service: orderData.service || '',
            subject: orderData.subject || '',
            message: orderData.message || '',
            amount: orderData.amount || 0,
            calculator_data: orderData.calculatorData || orderData.calculator_data || null
        };

        const result = await this.fetch('/api/orders', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        this.loading.submitOrder = false;

        return result;
    }

    /**
     * Clear all caches
     */
    clearCache() {
        this.cache = {
            services: null,
            portfolio: null,
            testimonials: null,
            faq: null,
            content: null,
            stats: null,
            settings: null
        };
        console.log('🗑️ API cache cleared');
    }

    /**
     * Check if data is loading
     */
    isLoading(resource) {
        return this.loading[resource] === true;
    }

    /**
     * Get error for resource
     */
    getError(resource) {
        return this.errors[resource] || null;
    }

    /**
     * Preload all data for faster page rendering
     */
    async preloadAll() {
        console.log('🔄 Preloading all data from API...');
        
        const promises = [
            this.getServices(),
            this.getPortfolio(),
            this.getTestimonials(),
            this.getFAQ(),
            this.getContent(),
            this.getStats(),
            this.getSettings()
        ];

        const results = await Promise.allSettled(promises);
        
        const failed = results.filter(r => r.status === 'rejected' || !r.value?.success);
        if (failed.length > 0) {
            console.warn(`⚠️ ${failed.length} API request(s) failed during preload`);
        } else {
            console.log('✅ All data preloaded successfully');
        }

        return {
            success: failed.length === 0,
            failedCount: failed.length
        };
    }
}

// Create global instance
const apiClient = new APIClient();
