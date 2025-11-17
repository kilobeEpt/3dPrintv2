// ========================================
// SEO METADATA MANAGER
// ========================================
// Динамическое управление SEO метаданными
// на основе конфигурации из config/seo-metadata.json

class SEOMetadataManager {
    constructor() {
        this.config = null;
        this.currentSection = 'home';
        this.isInitialized = false;
    }

    /**
     * Инициализация - загрузка конфигурации
     */
    async init() {
        try {
            const response = await fetch('/config/seo-metadata.json');
            if (!response.ok) {
                throw new Error(`Failed to load SEO config: ${response.status}`);
            }
            this.config = await response.json();
            this.isInitialized = true;
            
            // Устанавливаем базовые метатеги
            this.setBasicMetaTags();
            
            // Устанавливаем метатеги главной страницы
            this.updatePageMetadata('home');
            
            // Добавляем структурированные данные
            this.injectStructuredData();
            
            // Слушаем изменения секций (для SPA-подобной навигации)
            this.observeSectionChanges();
            
            return true;
        } catch (error) {
            console.error('SEO Metadata initialization error:', error);
            return false;
        }
    }

    /**
     * Установка базовых метатегов (charset, viewport, robots, theme-color)
     */
    setBasicMetaTags() {
        const defaults = this.config.default;
        
        // Charset
        this.setOrUpdateMeta('charset', null, defaults.charset);
        
        // Viewport
        this.setOrUpdateMeta('name', 'viewport', defaults.viewport);
        
        // Robots
        this.setOrUpdateMeta('name', 'robots', defaults.robots);
        
        // Theme Color
        this.setOrUpdateMeta('name', 'theme-color', defaults.themeColor);
        
        // Author
        this.setOrUpdateMeta('name', 'author', defaults.author);
        
        // Copyright
        this.setOrUpdateMeta('name', 'copyright', defaults.copyright);
        
        // Language
        document.documentElement.lang = defaults.lang;
        
        // Verification tags (если указаны)
        if (this.config.verification.googleSiteVerification) {
            this.setOrUpdateMeta('name', 'google-site-verification', 
                this.config.verification.googleSiteVerification);
        }
        
        if (this.config.verification.yandexVerification) {
            this.setOrUpdateMeta('name', 'yandex-verification', 
                this.config.verification.yandexVerification);
        }
    }

    /**
     * Обновление метаданных для конкретной страницы/секции
     */
    updatePageMetadata(section = 'home') {
        if (!this.isInitialized) {
            console.warn('SEO Manager not initialized');
            return;
        }

        const pageData = this.config.pages[section];
        if (!pageData) {
            console.warn(`No SEO data for section: ${section}`);
            return;
        }

        this.currentSection = section;

        // Title
        document.title = pageData.title;

        // Description
        this.setOrUpdateMeta('name', 'description', pageData.description);

        // Keywords
        this.setOrUpdateMeta('name', 'keywords', pageData.keywords);

        // Canonical URL
        this.setOrUpdateLink('canonical', pageData.canonical);

        // Open Graph Tags
        this.setOrUpdateMeta('property', 'og:type', pageData.ogType || 'website');
        this.setOrUpdateMeta('property', 'og:url', pageData.canonical);
        this.setOrUpdateMeta('property', 'og:title', pageData.ogTitle);
        this.setOrUpdateMeta('property', 'og:description', pageData.ogDescription);
        this.setOrUpdateMeta('property', 'og:image', pageData.ogImage);
        this.setOrUpdateMeta('property', 'og:site_name', this.config.default.siteName);
        this.setOrUpdateMeta('property', 'og:locale', this.config.default.locale);

        if (pageData.ogImageWidth) {
            this.setOrUpdateMeta('property', 'og:image:width', pageData.ogImageWidth);
        }
        if (pageData.ogImageHeight) {
            this.setOrUpdateMeta('property', 'og:image:height', pageData.ogImageHeight);
        }
        if (pageData.ogImageAlt) {
            this.setOrUpdateMeta('property', 'og:image:alt', pageData.ogImageAlt);
        }

        // Twitter Card Tags
        this.setOrUpdateMeta('name', 'twitter:card', pageData.twitterCard || 'summary_large_image');
        this.setOrUpdateMeta('name', 'twitter:title', pageData.twitterTitle || pageData.ogTitle);
        this.setOrUpdateMeta('name', 'twitter:description', pageData.twitterDescription || pageData.ogDescription);
        this.setOrUpdateMeta('name', 'twitter:image', pageData.twitterImage || pageData.ogImage);
        
        if (pageData.twitterImageAlt) {
            this.setOrUpdateMeta('name', 'twitter:image:alt', pageData.twitterImageAlt);
        }

        // Отправляем событие для аналитики
        this.dispatchMetadataUpdateEvent(section);
    }

    /**
     * Установка или обновление meta тега
     */
    setOrUpdateMeta(attribute, attributeValue, content) {
        if (attribute === 'charset') {
            let meta = document.querySelector('meta[charset]');
            if (!meta) {
                meta = document.createElement('meta');
                meta.setAttribute('charset', content);
                document.head.insertBefore(meta, document.head.firstChild);
            } else {
                meta.setAttribute('charset', content);
            }
            return;
        }

        let selector = `meta[${attribute}="${attributeValue}"]`;
        let meta = document.querySelector(selector);

        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute(attribute, attributeValue);
            document.head.appendChild(meta);
        }

        meta.setAttribute('content', content);
    }

    /**
     * Установка или обновление link тега (canonical)
     */
    setOrUpdateLink(rel, href) {
        let selector = `link[rel="${rel}"]`;
        let link = document.querySelector(selector);

        if (!link) {
            link = document.createElement('link');
            link.setAttribute('rel', rel);
            document.head.appendChild(link);
        }

        link.setAttribute('href', href);
    }

    /**
     * Внедрение структурированных данных (JSON-LD)
     */
    injectStructuredData() {
        const structuredData = this.config.structuredData;

        // Organization
        this.addStructuredDataScript('organization', structuredData.organization);

        // Breadcrumbs
        this.addStructuredDataScript('breadcrumbs', structuredData.breadcrumbs);

        // Service
        this.addStructuredDataScript('service', structuredData.service);
    }

    /**
     * Добавление script тега с JSON-LD данными
     */
    addStructuredDataScript(id, data) {
        // Удаляем существующий, если есть
        const existing = document.getElementById(`schema-${id}`);
        if (existing) {
            existing.remove();
        }

        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.id = `schema-${id}`;
        script.textContent = JSON.stringify(data, null, 2);
        document.head.appendChild(script);
    }

    /**
     * Наблюдение за изменением секций (для якорной навигации)
     */
    observeSectionChanges() {
        // Обработка изменения хеша в URL
        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.substring(1);
            const sectionMap = {
                'home': 'home',
                '': 'home',
                'services': 'services',
                'calculator': 'calculator',
                'portfolio': 'portfolio',
                'about': 'about',
                'contact': 'contact'
            };

            const section = sectionMap[hash] || 'home';
            this.updatePageMetadata(section);
        });

        // Обработка кликов по навигации
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                const hash = link.getAttribute('href').substring(1);
                const sectionMap = {
                    'home': 'home',
                    '': 'home',
                    'services': 'services',
                    'calculator': 'calculator',
                    'portfolio': 'portfolio',
                    'about': 'about',
                    'contact': 'contact'
                };

                const section = sectionMap[hash] || 'home';
                // Небольшая задержка для обновления после скролла
                setTimeout(() => {
                    this.updatePageMetadata(section);
                }, 100);
            }
        });

        // Intersection Observer для автоматического определения секции
        this.setupIntersectionObserver();
    }

    /**
     * Настройка Intersection Observer для автоматического обновления метатегов
     */
    setupIntersectionObserver() {
        const sections = document.querySelectorAll('section[id]');
        
        const observerOptions = {
            root: null,
            rootMargin: '-50% 0px -50% 0px',
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const sectionId = entry.target.id;
                    const sectionMap = {
                        'home': 'home',
                        'services': 'services',
                        'calculator': 'calculator',
                        'portfolio': 'portfolio',
                        'about': 'about',
                        'contact': 'contact'
                    };

                    const section = sectionMap[sectionId];
                    if (section && section !== this.currentSection) {
                        this.updatePageMetadata(section);
                    }
                }
            });
        }, observerOptions);

        sections.forEach(section => {
            observer.observe(section);
        });
    }

    /**
     * Отправка кастомного события при обновлении метаданных
     */
    dispatchMetadataUpdateEvent(section) {
        const event = new CustomEvent('seo-metadata-updated', {
            detail: {
                section: section,
                timestamp: Date.now()
            }
        });
        window.dispatchEvent(event);
    }

    /**
     * Получение текущей конфигурации
     */
    getConfig() {
        return this.config;
    }

    /**
     * Получение текущей секции
     */
    getCurrentSection() {
        return this.currentSection;
    }

    /**
     * Валидация метатегов (для отладки)
     */
    validate() {
        if (!this.isInitialized) {
            return {
                valid: false,
                errors: ['SEO Manager not initialized']
            };
        }

        const errors = [];
        const warnings = [];

        // Проверка обязательных тегов
        const requiredTags = [
            { selector: 'meta[name="description"]', name: 'Description' },
            { selector: 'meta[property="og:title"]', name: 'OG Title' },
            { selector: 'meta[property="og:description"]', name: 'OG Description' },
            { selector: 'meta[property="og:image"]', name: 'OG Image' },
            { selector: 'link[rel="canonical"]', name: 'Canonical URL' }
        ];

        requiredTags.forEach(tag => {
            const element = document.querySelector(tag.selector);
            if (!element) {
                errors.push(`Missing: ${tag.name}`);
            } else if (tag.selector.includes('meta')) {
                const content = element.getAttribute('content');
                if (!content || content.trim() === '') {
                    errors.push(`Empty content: ${tag.name}`);
                }
            }
        });

        // Проверка длины description
        const description = document.querySelector('meta[name="description"]');
        if (description) {
            const content = description.getAttribute('content');
            if (content.length < 50) {
                warnings.push('Description too short (< 50 chars)');
            }
            if (content.length > 160) {
                warnings.push('Description too long (> 160 chars)');
            }
        }

        // Проверка длины title
        if (document.title.length < 30) {
            warnings.push('Title too short (< 30 chars)');
        }
        if (document.title.length > 60) {
            warnings.push('Title too long (> 60 chars)');
        }

        return {
            valid: errors.length === 0,
            errors: errors,
            warnings: warnings
        };
    }

    /**
     * Экспорт текущих метаданных (для отладки)
     */
    exportCurrentMetadata() {
        const metadata = {
            title: document.title,
            description: document.querySelector('meta[name="description"]')?.content,
            keywords: document.querySelector('meta[name="keywords"]')?.content,
            canonical: document.querySelector('link[rel="canonical"]')?.href,
            ogTitle: document.querySelector('meta[property="og:title"]')?.content,
            ogDescription: document.querySelector('meta[property="og:description"]')?.content,
            ogImage: document.querySelector('meta[property="og:image"]')?.content,
            twitterCard: document.querySelector('meta[name="twitter:card"]')?.content,
            currentSection: this.currentSection
        };

        return metadata;
    }
}

// Создаем глобальный экземпляр
const seoManager = new SEOMetadataManager();

// Автоматическая инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        seoManager.init().then(success => {
            if (success) {
                console.log('✅ SEO Metadata Manager initialized');
            } else {
                console.error('❌ SEO Metadata Manager initialization failed');
            }
        });
    });
} else {
    // DOM уже загружен
    seoManager.init().then(success => {
        if (success) {
            console.log('✅ SEO Metadata Manager initialized');
        } else {
            console.error('❌ SEO Metadata Manager initialization failed');
        }
    });
}

// Экспорт для использования в других модулях
if (typeof window !== 'undefined') {
    window.seoManager = seoManager;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = seoManager;
}
