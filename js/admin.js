// ========================================
// ADMIN PANEL - COMPLETE VERSION
// ========================================

class AdminPanel {
    constructor() {
        this.currentPage = 'dashboard';
        this.currentUser = null;
        this.notifications = [];
        this.activeDropdown = null;
        
        // Initialize API client for backend communication
        this.api = new AdminAPIClient();
        
        // Pagination state for orders
        this.ordersPage = 1;
        this.ordersPerPage = 20;
        this.ordersTotalPages = 1;
        this.ordersTotal = 0;
        
        // Cached orders data for dashboard
        this.cachedOrders = [];
        this.lastOrdersFetch = null;
    }

    init() {
        this.checkAuth();
        this.initEventListeners();
    }

    // ========================================
    // AUTHENTICATION
    // ========================================

    async checkAuth() {
        const token = this.api.getToken();

        if (token) {
            // Validate token with backend
            const result = await this.api.getCurrentUser();
            
            if (result.success && result.data.data) {
                this.currentUser = result.data.data;
                this.updateUserDisplay();
                this.showDashboard();
                this.initAdminPanel();
            } else {
                // Token invalid, show login
                this.api.clearTokens();
                this.showLoginScreen();
            }
        } else {
            this.showLoginScreen();
        }
    }

    updateUserDisplay() {
        if (!this.currentUser) return;
        
        // Update sidebar user info
        const sidebarUserName = document.querySelector('.sidebar-footer .user-info strong');
        const sidebarUserLogin = document.querySelector('.sidebar-footer .user-info small');
        
        if (sidebarUserName) sidebarUserName.textContent = this.currentUser.name || 'Администратор';
        if (sidebarUserLogin) sidebarUserLogin.textContent = this.currentUser.login || 'admin';
        
        // Update header user menu
        const headerUserName = document.querySelector('#userMenuBtn span');
        if (headerUserName) headerUserName.textContent = this.currentUser.name || 'Администратор';
    }

    showLoginScreen() {
        document.getElementById('loginScreen').style.display = 'flex';
        document.getElementById('adminDashboard').style.display = 'none';
        this.initLoginForm();
    }

    showDashboard() {
        document.getElementById('loginScreen').style.display = 'none';
        document.getElementById('adminDashboard').style.display = 'flex';
    }

    initLoginForm() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        // Listen for 401 unauthorized events
        window.addEventListener('admin:unauthorized', () => {
            this.showNotification('Сессия истекла. Пожалуйста, войдите снова.', 'warning');
            this.currentUser = null;
            this.showLoginScreen();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const loginInput = document.getElementById('adminLogin').value;
            const password = document.getElementById('adminPassword').value;
            const submitBtn = form.querySelector('button[type="submit"]');

            // Disable form during submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вход...';
            form.querySelectorAll('input').forEach(input => input.disabled = true);

            const result = await this.api.login(loginInput, password);

            if (result.success) {
                this.currentUser = result.user;
                this.updateUserDisplay();
                this.showNotification('Вход выполнен успешно!', 'success');

                setTimeout(() => {
                    this.showDashboard();
                    this.initAdminPanel();
                }, 500);
            } else {
                this.showNotification(result.error || 'Неверный логин или пароль', 'error');
                document.getElementById('adminPassword').value = '';
                document.getElementById('adminPassword').classList.add('error-shake');
                setTimeout(() => {
                    document.getElementById('adminPassword').classList.remove('error-shake');
                }, 500);

                // Re-enable form
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Войти';
                form.querySelectorAll('input').forEach(input => input.disabled = false);
            }
        });
    }

    async logout() {
        if (confirm('Вы уверены, что хотите выйти?')) {
            await this.api.logout();
            this.currentUser = null;
            location.reload();
        }
    }

    // ========================================
    // INITIALIZATION
    // ========================================

    async initAdminPanel() {
        this.initNavigation();
        this.initSidebar();
        this.initTabs();
        this.initDropdowns();
        await this.loadDashboard();
        this.loadNotifications();
        await this.updateOrdersBadge();
    }

    initEventListeners() {
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.header-btn') && !e.target.closest('.user-menu') && !e.target.closest('.dropdown-menu')) {
                this.closeAllDropdowns();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllDropdowns();
                this.closeAllModals();
            }
        });
    }

    initNavigation() {
        const navItems = document.querySelectorAll('.nav-item');

        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();

                const page = item.getAttribute('data-page');
                this.navigateToPage(page);

                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');

                const title = item.querySelector('span').textContent;
                document.getElementById('pageTitle').textContent = title;
            });
        });
    }

    navigateToPage(pageId) {
        this.currentPage = pageId;

        document.querySelectorAll('.page').forEach(page => {
            page.classList.remove('active');
        });

        const targetPage = document.getElementById(`page-${pageId}`);
        if (targetPage) {
            targetPage.classList.add('active');
            this.loadPageData(pageId);
        }
    }

    async loadPageData(pageId) {
        switch (pageId) {
            case 'dashboard':
                await this.loadDashboard();
                break;
            case 'orders':
                this.loadOrders();
                break;
            case 'portfolio':
                this.loadPortfolio();
                break;
            case 'services':
                this.loadServices();
                break;
            case 'testimonials':
                this.loadTestimonials();
                break;
            case 'faq':
                this.loadFAQ();
                break;
            case 'calculator':
                this.loadCalculatorSettings();
                break;
            case 'content':
                this.loadContent();
                break;
            case 'forms':
                this.loadFormSettings();
                break;
            case 'settings':
                this.loadSettings();
                break;
        }
    }

    initSidebar() {
        const toggle = document.getElementById('toggleSidebar');
        const sidebar = document.querySelector('.admin-sidebar');

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }
    }

    initTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');

                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const tabs = document.querySelectorAll('.settings-tab');
                tabs.forEach(tab => tab.classList.remove('active'));

                const targetTab = document.getElementById(`tab-${tabId}`);
                if (targetTab) {
                    targetTab.classList.add('active');
                }
            });
        });
    }

    initDropdowns() {
        const notifBtn = document.getElementById('notificationsBtn');
        const notifDropdown = document.getElementById('notificationsDropdown');

        notifBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown(notifDropdown, notifBtn);
        });

        const settingsBtn = document.getElementById('quickSettingsBtn');
        const settingsDropdown = document.getElementById('quickSettingsDropdown');

        settingsBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown(settingsDropdown, settingsBtn);
        });

        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        userMenuBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown(userMenuDropdown, userMenuBtn);
            userMenuBtn.classList.toggle('active');
        });
    }

    toggleDropdown(dropdown, button) {
        if (!dropdown || !button) return;

        const isVisible = dropdown.style.display === 'block';

        this.closeAllDropdowns();

        if (!isVisible) {
            const rect = button.getBoundingClientRect();
            dropdown.style.display = 'block';
            dropdown.style.top = (rect.bottom + 10) + 'px';
            dropdown.style.right = (window.innerWidth - rect.right) + 'px';
            this.activeDropdown = dropdown;
        }
    }

    closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
        document.getElementById('userMenuBtn')?.classList.remove('active');
        this.activeDropdown = null;
    }

    // ========================================
    // DASHBOARD (с динамическими процентами #8)
    // ========================================

    async loadDashboard() {
        await this.loadDashboardStats();
        await this.loadRecentOrders();
        await this.loadActivityFeed();
        await this.loadPopularServices();
        await this.loadChart();
    }    async loadDashboardStats() {
        // Fetch orders for statistics (using a reasonable page size)
        const params = new URLSearchParams({
            page: 1,
            per_page: 100  // Get first 100 orders for stats
        });

        const result = await this.api.fetch(`/api/orders?${params.toString()}`);
        
        if (!result.success) {
            console.warn('Failed to load dashboard stats:', result.error);
            return;
        }

        const orders = result.data.data || [];
        const total = result.data.pagination?.total || orders.length;
        const now = new Date();
        const currentMonth = now.getMonth();
        const currentYear = now.getFullYear();

        const prevMonth = currentMonth === 0 ? 11 : currentMonth - 1;
        const prevMonthYear = currentMonth === 0 ? currentYear - 1 : currentYear;

        const totalOrders = total;
        document.getElementById('dashTotalOrders').textContent = totalOrders;

        const prevMonthOrders = orders.filter(o => {
            const orderDate = new Date(o.created_at);
            return orderDate.getMonth() === prevMonth && orderDate.getFullYear() === prevMonthYear;
        }).length;

        const ordersGrowth = this.calculateGrowth(totalOrders, prevMonthOrders);

        const monthRevenue = orders
            .filter(o => {
                const orderDate = new Date(o.created_at);
                return orderDate.getMonth() === currentMonth && orderDate.getFullYear() === currentYear;
            })
            .reduce((sum, o) => sum + (o.amount || 0), 0);

        document.getElementById('dashMonthRevenue').textContent = '₽' + monthRevenue.toLocaleString('ru-RU');

        const prevMonthRevenue = orders
            .filter(o => {
                const orderDate = new Date(o.created_at);
                return orderDate.getMonth() === prevMonth && orderDate.getFullYear() === prevMonthYear;
            })
            .reduce((sum, o) => sum + (o.amount || 0), 0);

        const revenueGrowth = this.calculateGrowth(monthRevenue, prevMonthRevenue);

        const uniqueClients = new Set(orders.map(o => o.client_email)).size;
        document.getElementById('dashTotalClients').textContent = uniqueClients;

        const prevMonthClients = new Set(
            orders
                .filter(o => {
                    const orderDate = new Date(o.created_at);
                    return orderDate.getMonth() === prevMonth && orderDate.getFullYear() === prevMonthYear;
                })
                .map(o => o.client_email)
        ).size;

        const clientsGrowth = this.calculateGrowth(uniqueClients, prevMonthClients);

        const processing = orders.filter(o => o.status === 'new' || o.status === 'processing').length;
        document.getElementById('dashProcessing').textContent = processing;

        const statBoxes = document.querySelectorAll('.stat-box');
        if (statBoxes[0]) {
            this.updateStatChange(statBoxes[0], ordersGrowth);
        }
        if (statBoxes[1]) {
            this.updateStatChange(statBoxes[1], revenueGrowth);
        }
        if (statBoxes[2]) {
            this.updateStatChange(statBoxes[2], clientsGrowth);
        }
        if (statBoxes[3]) {
            const processingPercent = totalOrders > 0 ? ((processing / totalOrders) * 100).toFixed(1) : 0;
            const changeSpan = statBoxes[3].querySelector('.stat-change');
            if (changeSpan) {
                changeSpan.innerHTML = `<i class="fas fa-equals"></i> ${processingPercent}% от общего`;
                changeSpan.className = 'stat-change';
                if (processing > 5) {
                    changeSpan.classList.add('negative');
                } else {
                    changeSpan.classList.add('positive');
                }
            }
        }

        // Cache orders for other dashboard widgets
        this.cachedOrders = orders;
        this.lastOrdersFetch = Date.now();
    }


    calculateGrowth(current, previous) {
        if (previous === 0) return current > 0 ? 100 : 0;
        return (((current - previous) / previous) * 100).toFixed(1);
    }

    updateStatChange(statBox, growth) {
        const changeSpan = statBox.querySelector('.stat-change');
        if (!changeSpan) return;

        const isPositive = growth >= 0;
        const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';

        changeSpan.innerHTML = `<i class="fas ${icon}"></i> ${Math.abs(growth)}%`;
        changeSpan.className = 'stat-change';
        changeSpan.classList.add(isPositive ? 'positive' : 'negative');
    }    async loadRecentOrders() {
        let orders = this.cachedOrders;
        
        // Fetch if not cached or stale
        if (!orders.length || !this.lastOrdersFetch || (Date.now() - this.lastOrdersFetch > 60000)) {
            const params = new URLSearchParams({
                page: 1,
                per_page: 10
            });

            const result = await this.api.fetch(`/api/orders?${params.toString()}`);
            
            if (!result.success) {
                console.warn('Failed to load recent orders:', result.error);
                const container = document.getElementById('recentOrders');
                if (container) {
                    container.innerHTML = '<p style="text-align: center; color: var(--admin-danger); padding: 20px;">Ошибка загрузки</p>';
                }
                return;
            }

            orders = result.data.data || [];
            this.cachedOrders = orders;
        }

        const recentOrders = orders.slice(0, 5);

        const container = document.getElementById('recentOrders');
        if (!container) return;

        if (recentOrders.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--admin-text-secondary); padding: 20px;">Заказов пока нет</p>';
            return;
        }

        container.innerHTML = recentOrders.map(order => `
            <div class="order-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid var(--admin-border); cursor: pointer; transition: background 0.2s;" onclick="admin.viewOrder(${order.id})">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                        <strong>#${order.order_number || order.id}</strong>
                        <span class="type-badge type-${order.type || 'order'}">
                            ${order.type === 'contact' ? 'Обращение' : 'Заказ'}
                        </span>
                    </div>
                    <div style="color: var(--admin-text-secondary); font-size: 14px;">
                        ${order.client_name} • ${order.service || order.subject || '-'}
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600; margin-bottom: 5px;">₽${(order.amount || 0).toLocaleString('ru-RU')}</div>
                    <span class="status-badge status-${order.status}">
                        ${this.getStatusName(order.status)}
                    </span>
                </div>
            </div>
        `).join('');
    }    async loadActivityFeed() {
        let orders = this.cachedOrders;
        
        if (!orders.length) {
            const params = new URLSearchParams({
                page: 1,
                per_page: 10
            });

            const result = await this.api.fetch(`/api/orders?${params.toString()}`);
            
            if (result.success) {
                orders = result.data.data || [];
            }
        }

        const recentOrders = orders.slice(0, 4);
        const container = document.getElementById('activityFeed');
        if (!container) return;

        if (recentOrders.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--admin-text-secondary); padding: 20px;">Нет активности</p>';
            return;
        }

        container.innerHTML = recentOrders.map(order => {
            const iconClass = order.status === 'completed' ? 'success' :
                order.status === 'new' ? 'info' : 'warning';
            const icon = order.status === 'completed' ? 'fa-check' :
                order.status === 'new' ? 'fa-shopping-cart' : 'fa-clock';

            return `
                <div class="activity-item">
                    <div class="activity-icon ${iconClass}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="activity-content">
                        <p>${order.type === 'contact' ? 'Новое обращение' : 'Новый заказ'} от <strong>${order.client_name}</strong></p>
                        <span>${this.getRelativeTime(order.created_at)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }    async loadPopularServices() {
        let orders = this.cachedOrders;
        
        if (!orders.length) {
            const params = new URLSearchParams({
                page: 1,
                per_page: 100
            });

            const result = await this.api.fetch(`/api/orders?${params.toString()}`);
            
            if (result.success) {
                orders = result.data.data || [];
            }
        }

        const services = {};

        orders.forEach(order => {
            const service = order.service || 'Другое';
            services[service] = (services[service] || 0) + 1;
        });

        const total = orders.length || 1;
        const sorted = Object.entries(services)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 4);

        const container = document.getElementById('popularServices');
        if (!container) return;

        if (sorted.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--admin-text-secondary); padding: 20px;">Нет данных</p>';
            return;
        }

        container.innerHTML = sorted.map(([service, count]) => {
            const percent = Math.round((count / total) * 100);
            return `
                <div class="service-item">
                    <div class="service-bar">
                        <div class="service-progress" style="width: ${percent}%"></div>
                    </div>
                    <div class="service-stats">
                        <span>${service}</span>
                        <strong>${count} (${percent}%)</strong>
                    </div>
                </div>
            `;
        }).join('');
    }    async loadChart() {
        const ctx = document.getElementById('ordersChart');
        if (!ctx) return;

        let orders = this.cachedOrders;
        
        if (!orders.length) {
            const params = new URLSearchParams({
                page: 1,
                per_page: 100
            });

            const result = await this.api.fetch(`/api/orders?${params.toString()}`);
            
            if (result.success) {
                orders = result.data.data || [];
            }
        }

        const last7Days = this.getLast7Days();

        const data = last7Days.map(date => {
            return orders.filter(o => {
                const orderDate = new Date(o.created_at).toDateString();
                return orderDate === date.toDateString();
            }).length;
        });

        if (window.ordersChartInstance) {
            window.ordersChartInstance.destroy();
        }

        window.ordersChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: last7Days.map(d => d.toLocaleDateString('ru-RU', { weekday: 'short' })),
                datasets: [{
                    label: 'Заказы',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    getLast7Days() {
        const days = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            days.push(date);
        }
        return days;
    }
    // ========================================
    // ORDERS MANAGEMENT
    // ========================================

    async loadOrders() {
        await await this.renderOrdersTable();
        this.initOrdersFilters();
        await await this.updateOrdersBadge();
    }    async renderOrdersTable() {
        const tbody = document.getElementById('ordersTable');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Загрузка заказов...</td></tr>';

        const statusFilter = document.getElementById('orderStatusFilter')?.value;
        const typeFilter = document.getElementById('orderTypeFilter')?.value;
        const searchFilter = document.getElementById('orderSearchFilter')?.value;

        const params = new URLSearchParams({
            page: this.ordersPage,
            per_page: this.ordersPerPage
        });

        if (statusFilter && statusFilter !== 'all') {
            params.append('status', statusFilter);
        }

        if (typeFilter && typeFilter !== 'all') {
            params.append('type', typeFilter);
        }

        if (searchFilter) {
            params.append('search', searchFilter);
        }

        const result = await this.api.fetch(`/api/orders?${params.toString()}`);

        if (!result.success) {
            tbody.innerHTML = `<tr><td colspan="11" style="text-align: center; padding: 40px; color: var(--admin-danger);">Ошибка загрузки: ${result.error}</td></tr>`;
            return;
        }

        const orders = result.data.data || [];
        const pagination = result.data.pagination || {};
        
        this.ordersTotal = pagination.total || 0;
        this.ordersTotalPages = pagination.total_pages || 1;
        this.cachedOrders = orders;
        this.lastOrdersFetch = Date.now();

        if (orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 40px; color: var(--admin-text-secondary);">Заказов не найдено</td></tr>';
            this.renderPagination(0, 1, 1);
            return;
        }

        tbody.innerHTML = orders.map((order) => `
        <tr>
            <td><input type="checkbox" class="order-checkbox" data-id="${order.id}"></td>
            <td><strong>#${order.order_number || '-'}</strong></td>
            <td>
                <span class="type-badge type-${order.type || 'order'}">
                    ${order.type === 'contact' ? 'Обращение' : 'Заказ'}
                </span>
            </td>
            <td>${order.client_name || '-'}</td>
            <td style="font-size: 13px;">
                ${order.client_email || '-'}<br>
                ${order.client_phone || '-'}
                ${order.telegram ? `<br><i class="fab fa-telegram" style="color: var(--admin-info);"></i> ${order.telegram}` : ''}
            </td>
            <td>${order.service || order.subject || '-'}</td>
            <td>${this.formatDate(order.created_at)}</td>
            <td><strong>₽${(order.amount || 0).toLocaleString('ru-RU')}</strong></td>
            <td>
                <select class="status-select status-${order.status}" onchange="admin.quickChangeStatus(${order.id}, this.value)" onclick="event.stopPropagation()">
                    <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                    <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>В работе</option>
                    <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Выполнен</option>
                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменён</option>
                </select>
            </td>
            <td>
                <div class="action-btns">
                    <button class="action-btn view" onclick="admin.viewOrder(${order.id})" title="Просмотр">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn edit" onclick="admin.editOrder(${order.id})" title="Редактировать">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="admin.deleteOrder(${order.id})" title="Удалить">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

        this.renderPagination(pagination.total, pagination.page, pagination.total_pages);
    }

    renderPagination(total, currentPage, totalPages) {
        const container = document.querySelector('.orders-pagination') || this.createPaginationContainer();
        if (!container) return;

        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'flex';
        
        let paginationHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(1)" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-angle-left"></i>
                </button>
                <span style="padding: 0 15px; color: var(--admin-text-secondary);">
                    Страница ${currentPage} из ${totalPages} (всего: ${total})
                </span>
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-angle-right"></i>
                </button>
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </div>
        `;

        container.innerHTML = paginationHTML;
    }

    createPaginationContainer() {
        const table = document.querySelector('#ordersTable')?.closest('.card');
        if (!table) return null;

        const existing = document.querySelector('.orders-pagination');
        if (existing) return existing;

        const container = document.createElement('div');
        container.className = 'orders-pagination';
        container.style.cssText = 'display: flex; justify-content: center; align-items: center; padding: 20px; border-top: 1px solid var(--admin-border);';
        
        table.appendChild(container);
        return container;
    }

    async goToOrdersPage(page) {
        if (page < 1 || page > this.ordersTotalPages) return;
        this.ordersPage = page;
        await await this.renderOrdersTable();
    }

    renderPagination(total, currentPage, totalPages) {
        const container = document.querySelector('.orders-pagination') || this.createPaginationContainer();
        if (!container) return;

        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'flex';
        
        let paginationHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(1)" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-angle-left"></i>
                </button>
                <span style="padding: 0 15px; color: var(--admin-text-secondary);">
                    Страница ${currentPage} из ${totalPages} (всего: ${total})
                </span>
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-angle-right"></i>
                </button>
                <button class="btn btn-sm" onclick="admin.goToOrdersPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </div>
        `;

        container.innerHTML = paginationHTML;
    }

    createPaginationContainer() {
        const table = document.querySelector('#ordersTable')?.closest('.card');
        if (!table) return null;

        const existing = document.querySelector('.orders-pagination');
        if (existing) return existing;

        const container = document.createElement('div');
        container.className = 'orders-pagination';
        container.style.cssText = 'display: flex; justify-content: center; align-items: center; padding: 20px; border-top: 1px solid var(--admin-border);';
        
        table.appendChild(container);
        return container;
    }    async quickChangeStatus(id, newStatus) {
        const result = await this.api.fetch(`/api/orders/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ status: newStatus })
        });

        if (!result.success) {
            this.showNotification(`Ошибка изменения статуса: ${result.error}`, 'error');
            return;
        }

        this.showNotification(`✅ Статус изменён на "${this.getStatusName(newStatus)}"`, 'success');
        await await this.renderOrdersTable();
        await await this.updateOrdersBadge();
        await await this.loadDashboard();
    }    async editOrder(id) {
        const result = await this.api.fetch(`/api/orders/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки заказа: ${result.error}`, 'error');
            return;
        }

        const order = result.data.data;
        const isCalculatorOrder = order.type === 'order' && order.calculator_data;

        let formHTML = `
        <form id="editOrderForm" style="max-width: 600px;">
            <div class="form-group">
                <label>Номер заказа</label>
                <input type="text" class="form-control" value="${order.order_number || ''}" readonly style="background: var(--admin-bg);">
            </div>
            
            <div class="form-group">
                <label>Тип заявки</label>
                <select class="form-control" id="editOrderType">
                    <option value="order" ${order.type === 'order' ? 'selected' : ''}>Заказ</option>
                    <option value="contact" ${order.type === 'contact' ? 'selected' : ''}>Обращение</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Имя клиента <span style="color: var(--admin-danger);">*</span></label>
                <input type="text" class="form-control" id="editClientName" value="${order.client_name || ''}" required>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Email <span style="color: var(--admin-danger);">*</span></label>
                    <input type="email" class="form-control" id="editClientEmail" value="${order.client_email || ''}" required>
                </div>
                
                <div class="form-group">
                    <label>Телефон <span style="color: var(--admin-danger);">*</span></label>
                    <input type="tel" class="form-control" id="editClientPhone" value="${order.client_phone || ''}" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Telegram</label>
                <input type="text" class="form-control" id="editTelegram" value="${order.telegram || ''}" placeholder="@username">
            </div>
            
            <div class="form-group">
                <label>Услуга/Тема</label>
                <input type="text" class="form-control" id="editService" value="${order.service || order.subject || ''}">
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Сумма (₽)</label>
                    <input type="number" class="form-control" id="editAmount" value="${order.amount || 0}" min="0">
                </div>
                
                <div class="form-group">
                    <label>Статус <span style="color: var(--admin-danger);">*</span></label>
                    <select class="form-control" id="editStatus" required>
                        <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>В работе</option>
                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Выполнен</option>
                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменён</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Детали / Сообщение</label>
                <textarea class="form-control" id="editDetails" rows="4">${order.details || order.message || ''}</textarea>
            </div>
            
            ${isCalculatorOrder ? `
            <div class="alert alert-info" style="margin: 20px 0; padding: 15px; background: rgba(59,130,246,0.1); border-radius: 10px; border-left: 4px solid var(--admin-info);">
                <strong><i class="fas fa-info-circle"></i> Данные расчёта калькулятора:</strong><br>
                <div style="margin-top: 10px; font-size: 14px; color: var(--admin-text-secondary);">
                    <strong>Технология:</strong> ${order.calculator_data.technology || '-'}<br>
                    <strong>Материал:</strong> ${order.calculator_data.material || '-'}<br>
                    <strong>Вес:</strong> ${order.calculator_data.weight || 0}г<br>
                    <strong>Количество:</strong> ${order.calculator_data.quantity || 0} шт<br>
                    <strong>Заполнение:</strong> ${order.calculator_data.infill || 0}%<br>
                    <strong>Качество:</strong> ${order.calculator_data.quality || '-'}
                </div>
            </div>
            ` : ''}
            
            <div class="form-group" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: var(--admin-bg); border-radius: 10px;">
                <i class="fas fa-clock" style="color: var(--admin-text-secondary);"></i>
                <div style="flex: 1; font-size: 13px; color: var(--admin-text-secondary);">
                    <strong>Создан:</strong> ${this.formatDate(order.created_at)}<br>
                    ${order.updated_at ? `<strong>Обновлён:</strong> ${this.formatDate(order.updated_at)}` : ''}
                </div>
                <div style="text-align: right;">
                    ${order.telegram_sent ?
                '<span class="telegram-status telegram-sent"><i class="fab fa-telegram"></i> Отправлено</span>' :
                '<span class="telegram-status telegram-failed"><i class="fab fa-telegram"></i> Не отправлено</span>'}
                </div>
            </div>
            
            <div class="modal-footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--admin-border); display: flex; justify-content: space-between; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="admin.closeAllModals()">
                    <i class="fas fa-times"></i>
                    Отмена
                </button>
                <div style="display: flex; gap: 10px;">
                    ${!order.telegram_sent ? `
                    <button type="button" class="btn btn-outline" onclick="admin.resendToTelegram(${order.id})">
                        <i class="fab fa-telegram"></i>
                        Отправить в Telegram
                    </button>
                    ` : ''}
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Сохранить изменения
                    </button>
                </div>
            </div>
        </form>
    `;

        const modal = this.createModal(`Редактирование заявки #${order.order_number || order.id}`, formHTML);

        const form = document.getElementById('editOrderForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';

            const updatedData = {
                type: document.getElementById('editOrderType').value,
                client_name: document.getElementById('editClientName').value,
                client_email: document.getElementById('editClientEmail').value,
                client_phone: document.getElementById('editClientPhone').value,
                telegram: document.getElementById('editTelegram').value,
                service: document.getElementById('editService').value,
                subject: document.getElementById('editService').value,
                amount: parseFloat(document.getElementById('editAmount').value) || 0,
                status: document.getElementById('editStatus').value,
                details: document.getElementById('editDetails').value,
                message: document.getElementById('editDetails').value
            };

            const result = await this.api.fetch(`/api/orders/${id}`, {
                method: 'PUT',
                body: JSON.stringify(updatedData)
            });

            if (!result.success) {
                this.showNotification(`Ошибка сохранения: ${result.error}`, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить изменения';
                return;
            }

            this.showNotification('✅ Заявка успешно обновлена', 'success');
            this.closeAllModals();
            await await this.renderOrdersTable();
            await await this.updateOrdersBadge();
            await await this.loadDashboard();
        });
    }


    initOrdersFilters() {
        ['orderStatusFilter', 'orderTypeFilter', 'orderSearchFilter'].forEach(id => {
            const filter = document.getElementById(id);
            if (filter) {
                const event = id.includes('Search') ? 'input' : 'change';
                filter.addEventListener(event, async () => {
                    await this.renderOrdersTable();
                });
            }
        });
    }    async updateOrdersBadge() {
        const params = new URLSearchParams({
            status: 'new',
            per_page: 1,
            page: 1
        });

        const result = await this.api.fetch(`/api/orders?${params.toString()}`);

        if (!result.success) {
            console.warn('Failed to update orders badge:', result.error);
            return;
        }

        const newOrdersCount = result.data.pagination?.total || 0;

        const badge = document.getElementById('ordersBadge');
        if (badge) {
            badge.textContent = newOrdersCount;
            badge.style.display = newOrdersCount > 0 ? 'block' : 'none';
        }

        const notifDot = document.getElementById('notificationDot');
        if (notifDot) {
            notifDot.style.display = newOrdersCount > 0 ? 'block' : 'none';
        }
    }    async viewOrder(id) {
        const result = await this.api.fetch(`/api/orders/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки заказа: ${result.error}`, 'error');
            return;
        }

        const order = result.data.data;

        let content = `
        <div class="detail-section">
            <h4>Информация о клиенте</h4>
            <p><strong>Имя:</strong> ${order.client_name || '-'}</p>
            <p><strong>Email:</strong> ${order.client_email || '-'}</p>
            <p><strong>Телефон:</strong> ${order.client_phone || '-'}</p>
            ${order.telegram ? `<p><strong>Telegram:</strong> ${order.telegram}</p>` : ''}
        </div>
    `;

        if (order.type !== 'contact' && order.calculator_data) {
            const calc = order.calculator_data;
            content += `
                <div class="detail-section">
                    <h4>Детали заказа</h4>
                    <p><strong>Услуга:</strong> ${order.service || '-'}</p>
                    <p><strong>Сумма:</strong> ₽${(order.amount || 0).toLocaleString('ru-RU')}</p>
                    <p><strong>Технология:</strong> ${(calc.technology || '-').toUpperCase()}</p>
                    <p><strong>Материал:</strong> ${calc.material || '-'}</p>
                    <p><strong>Вес:</strong> ${calc.weight || '-'}г</p>
                    <p><strong>Количество:</strong> ${calc.quantity || '-'} шт</p>
                    <p><strong>Заполнение:</strong> ${calc.infill || '-'}%</p>
                    <p><strong>Качество:</strong> ${calc.quality || '-'}</p>
                    <p><strong>Срок:</strong> ${calc.timeEstimate || calc.time_estimate || '-'}</p>
                </div>
            `;
        }

        if (order.subject) {
            content += `
                <div class="detail-section">
                    <h4>Тема обращения</h4>
                    <p>${order.subject}</p>
                </div>
            `;
        }

        if (order.message || order.details) {
            content += `
                <div class="detail-section">
                    <h4>Сообщение</h4>
                    <p>${order.message || order.details || '-'}</p>
                </div>
            `;
        }

        content += `
            <div class="detail-section">
                <h4>Статус</h4>
                <p><strong>Текущий статус:</strong> <span class="status-badge status-${order.status}">${this.getStatusName(order.status)}</span></p>
                <p><strong>Дата создания:</strong> ${this.formatDate(order.created_at)}</p>
                ${order.telegram_sent ?
                '<p><span class="telegram-status telegram-sent"><i class="fab fa-telegram"></i> Отправлено в Telegram</span></p>' :
                '<p><span class="telegram-status telegram-failed"><i class="fab fa-telegram"></i> Не отправлено</span></p>'}
            </div>
        `;

        content += `
    <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        ${order.status === 'new' ? `
        <button class="btn btn-primary" onclick="admin.markOrderProcessed(${order.id})">
            <i class="fas fa-check"></i>
            Отметить как обработанный
        </button>
        ` : ''}
        ${order.status !== 'completed' ? `
        <button class="btn btn-outline" onclick="admin.markOrderCompleted(${order.id})">
            <i class="fas fa-check-double"></i>
            Выполнен
        </button>
        ` : ''}
        <button class="btn btn-outline" onclick="admin.editOrder(${order.id})">
            <i class="fas fa-edit"></i>
            Редактировать
        </button>
        ${!order.telegram_sent ? `
        <button class="btn btn-outline" onclick="admin.resendToTelegram(${order.id})">
            <i class="fab fa-telegram"></i>
            Отправить в Telegram
        </button>
        ` : ''}
    </div>
`;

        this.createModal(`Заказ #${order.order_number || order.id}`, content);
    }    async markOrderCompleted(id) {
        if (!confirm('Отметить заявку как выполненную?')) return;

        const result = await this.api.fetch(`/api/orders/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ status: 'completed' })
        });

        if (!result.success) {
            this.showNotification(`Ошибка: ${result.error}`, 'error');
            return;
        }

        this.showNotification('✅ Заявка отмечена как выполненная', 'success');
        this.closeAllModals();
        await await this.renderOrdersTable();
        await await this.updateOrdersBadge();
        await await this.loadDashboard();
    }    async markOrderProcessed(id) {
        const result = await this.api.fetch(`/api/orders/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ status: 'processing' })
        });

        if (!result.success) {
            this.showNotification(`Ошибка: ${result.error}`, 'error');
            return;
        }

        this.showNotification('Заказ переведён в обработку', 'success');
        this.closeAllModals();
        await await this.renderOrdersTable();
        await await this.updateOrdersBadge();
        await await this.loadDashboard();
    }
    async deleteOrder(id) {
        if (!confirm('Удалить этот заказ? Это действие нельзя отменить.')) return;

        const result = await this.api.fetch(`/api/orders/${id}`, {
            method: 'DELETE'
        });

        if (!result.success) {
            this.showNotification(`Ошибка удаления: ${result.error}`, 'error');
            return;
        }

        this.showNotification('Заказ удалён', 'success');
        await await this.renderOrdersTable();
        await await this.updateOrdersBadge();
        await await this.loadDashboard();
    }

        async bulkChangeStatus() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.getAttribute('data-id'));

        if (ids.length === 0) {
            this.showNotification('Выберите заявки для изменения статуса', 'warning');
            return;
        }

        const newStatus = prompt('Введите новый статус:\nnew - Новый\nprocessing - В работе\ncompleted - Выполнен\ncancelled - Отменён');

        if (!['new', 'processing', 'completed', 'cancelled'].includes(newStatus)) {
            this.showNotification('Неверный статус', 'error');
            return;
        }

        let successCount = 0;
        let errorCount = 0;

        for (const id of ids) {
            const result = await this.api.fetch(`/api/orders/${id}`, {
                method: 'PUT',
                body: JSON.stringify({ status: newStatus })
            });

            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
        }

        if (errorCount > 0) {
            this.showNotification(`Обновлено: ${successCount}, ошибок: ${errorCount}`, 'warning');
        } else {
            this.showNotification(`✅ Статус изменён для ${successCount} заявок`, 'success');
        }

        await this.renderOrdersTable();
        await this.updateOrdersBadge();
        await this.loadDashboard();
    }

    async resendToTelegram(id) {
        this.showNotification('Отправка в Telegram...', 'info');

        const result = await this.api.fetch(`/api/orders/${id}/resend-telegram`, {
            method: 'POST'
        });

        if (!result.success) {
            this.showNotification(`Ошибка отправки: ${result.error}`, 'error');
            return;
        }

        this.showNotification('✅ Успешно отправлено в Telegram', 'success');
        this.closeAllModals();
        await this.viewOrder(id);
    }


    async exportOrders() {
        // Fetch all orders for export
        const params = new URLSearchParams({
            page: 1,
            per_page: 1000  // Get a large batch for export
        });

        const result = await this.api.fetch(`/api/orders?${params.toString()}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка экспорта: ${result.error}`, 'error');
            return;
        }

        const orders = result.data.data || [];
        const blob = new Blob([JSON.stringify(orders, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        URL.revokeObjectURL(url);
        link.download = `orders_${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        this.showNotification('Заказы экспортированы', 'success');
    }

        async bulkDeleteOrders() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.getAttribute('data-id'));

        if (ids.length === 0) {
            this.showNotification('Выберите заказы для удаления', 'warning');
            return;
        }

        if (!confirm(`Удалить ${ids.length} заказов? Это действие нельзя отменить.`)) return;

        let successCount = 0;
        let errorCount = 0;

        for (const id of ids) {
            const result = await this.api.fetch(`/api/orders/${id}`, {
                method: 'DELETE'
            });

            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
        }

        if (errorCount > 0) {
            this.showNotification(`Удалено: ${successCount}, ошибок: ${errorCount}`, 'warning');
        } else {
            this.showNotification(`Удалено заказов: ${successCount}`, 'success');
        }

        await this.renderOrdersTable();
        await this.updateOrdersBadge();
        await this.loadDashboard();
    }

    addOrder() {
        const fields = [
            new FormField({
                name: 'clientName',
                label: 'Имя клиента',
                type: 'text',
                required: true,
                placeholder: 'Иван Петров'
            }),
            new FormField({
                name: 'clientPhone',
                label: 'Телефон',
                type: 'tel',
                required: true,
                placeholder: '+7 999 123-45-67'
            }),
            new FormField({
                name: 'clientEmail',
                label: 'Email',
                type: 'email',
                required: true,
                placeholder: 'example@mail.com'
            }),
            new FormField({
                name: 'service',
                label: 'Услуга',
                type: 'text',
                required: true,
                placeholder: 'FDM печать'
            }),
            new FormField({
                name: 'amount',
                label: 'Сумма (₽)',
                type: 'number',
                required: true,
                placeholder: '5000',
                value: '0'
            }),
            new FormField({
                name: 'details',
                label: 'Детали заказа',
                type: 'textarea',
                placeholder: 'Дополнительная информация'
            }),
            new FormField({
                name: 'status',
                label: 'Статус',
                type: 'select',
                required: true,
                value: 'new',
                options: [
                    { value: 'new', label: 'Новый' },
                    { value: 'processing', label: 'В работе' },
                    { value: 'completed', label: 'Выполнен' },
                    { value: 'cancelled', label: 'Отменён' }
                ]
            })
        ];

        this.showFormModal('Добавить заказ вручную', fields, async (formData) => {
            const order = {
                type: 'order',
                clientName: formData.clientName,
                clientPhone: formData.clientPhone,
                clientEmail: formData.clientEmail,
                service: formData.service,
                amount: parseFloat(formData.amount) || 0,
                details: formData.details || '',
                status: formData.status || 'new',
                orderNumber: this.generateOrderNumber(),
                telegramSent: false
            };

            const savedOrder = db.addItem('orders', order);

            if (CONFIG.features.telegramNotifications && CONFIG.telegram.chatId && order.status === 'new') {
                telegramBot.sendOrderNotification(savedOrder).then(result => {
                    if (result.success) {
                        db.updateItem('orders', savedOrder.id, { telegramSent: true });
                    }
                });
            }

            this.showNotification('Заказ успешно добавлен', 'success');
            await this.renderOrdersTable();
            await this.updateOrdersBadge();
            await this.loadDashboard();
        });
    }

    generateOrderNumber() {
        const orders = db.getData('orders') || [];
        const maxNumber = orders.reduce((max, o) => {
            const num = parseInt(o.orderNumber) || 0;
            return num > max ? num : max;
        }, 1000);
        return (maxNumber + 1).toString();
    }

    // ========================================
    // PORTFOLIO MANAGEMENT
    // ========================================

    async loadPortfolio() {
        await this.renderPortfolio();
    }

    async renderPortfolio() {
        const grid = document.getElementById('portfolioAdminGrid');
        if (!grid) return;

        grid.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--admin-text-secondary); grid-column: 1/-1;"><i class="fas fa-spinner fa-spin"></i> Загрузка портфолио...</div>';

        const result = await this.api.fetch('/api/portfolio');

        if (!result.success) {
            grid.innerHTML = `<p style="text-align: center; padding: 40px; color: var(--admin-danger); grid-column: 1/-1;">Ошибка загрузки: ${result.error}</p>`;
            return;
        }

        const items = result.data.data || [];

        if (items.length === 0) {
            grid.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--admin-text-secondary); grid-column: 1/-1;">Портфолио пусто. Добавьте первую работу!</p>';
            return;
        }

        grid.innerHTML = items.map(item => `
            <div class="portfolio-admin-item hover-lift" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <img src="${item.image_url}" alt="${item.title}" style="width: 100%; height: 200px; object-fit: cover;" loading="lazy">
                <div style="padding: 20px;">
                    <h3 style="margin-bottom: 10px; font-size: 18px;">${item.title}</h3>
                    <p style="color: var(--admin-text-secondary); font-size: 14px; margin-bottom: 15px; line-height: 1.5;">${item.description}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="status-badge" style="background: rgba(99,102,241,0.1); color: var(--admin-primary);">
                            ${this.getCategoryName(item.category)}
                        </span>
                        <div class="action-btns">
                            <button class="action-btn edit" onclick="admin.editPortfolio(${item.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete" onclick="admin.deletePortfolio(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    addPortfolio() {
        const fields = [
            new FormField({
                name: 'title',
                label: 'Название проекта',
                type: 'text',
                required: true,
                placeholder: 'Например: Прототип корпуса'
            }),
            new FormField({
                name: 'category',
                label: 'Категория',
                type: 'select',
                required: true,
                options: [
                    { value: 'prototype', label: 'Прототипы' },
                    { value: 'functional', label: 'Функциональные' },
                    { value: 'art', label: 'Художественные' },
                    { value: 'industrial', label: 'Промышленные' }
                ]
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true,
                placeholder: 'Краткое описание проекта'
            }),
            new FormField({
                name: 'image_url',
                label: 'URL изображения',
                type: 'url',
                required: true,
                placeholder: 'https://...',
                helpText: 'Ссылка на изображение проекта'
            }),
            new FormField({
                name: 'details',
                label: 'Детали проекта',
                type: 'textarea',
                placeholder: 'Материал, технология, время печати...'
            })
        ];

        this.showFormModal('Добавить работу в портфолио', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            const result = await this.api.fetch('/api/admin/portfolio', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (!result.success) {
                this.showNotification(`Ошибка: ${result.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Работа добавлена в портфолио', 'success');
            this.closeAllModals();
            await this.renderPortfolio();
        });
    }

    async editPortfolio(id) {
        const result = await this.api.fetch(`/api/portfolio/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки: ${result.error}`, 'error');
            return;
        }

        const item = result.data.data;

        const fields = [
            new FormField({
                name: 'title',
                label: 'Название проекта',
                type: 'text',
                required: true,
                value: item.title
            }),
            new FormField({
                name: 'category',
                label: 'Категория',
                type: 'select',
                required: true,
                value: item.category,
                options: [
                    { value: 'prototype', label: 'Прототипы' },
                    { value: 'functional', label: 'Функциональные' },
                    { value: 'art', label: 'Художественные' },
                    { value: 'industrial', label: 'Промышленные' }
                ]
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true,
                value: item.description
            }),
            new FormField({
                name: 'image_url',
                label: 'URL изображения',
                type: 'url',
                required: true,
                value: item.image_url
            }),
            new FormField({
                name: 'details',
                label: 'Детали проекта',
                type: 'textarea',
                value: item.details || ''
            })
        ];

        this.showFormModal('Редактировать работу', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            const updateResult = await this.api.fetch(`/api/admin/portfolio/${id}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });

            if (!updateResult.success) {
                this.showNotification(`Ошибка: ${updateResult.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Работа обновлена', 'success');
            this.closeAllModals();
            await this.renderPortfolio();
        });
    }

    async deletePortfolio(id) {
        if (!confirm('Удалить эту работу из портфолио?')) return;

        const result = await this.api.fetch(`/api/admin/portfolio/${id}`, {
            method: 'DELETE'
        });

        if (!result.success) {
            this.showNotification(`Ошибка удаления: ${result.error}`, 'error');
            return;
        }

        this.showNotification('Работа удалена', 'success');
        await this.renderPortfolio();
    }

    getCategoryName(category) {
        const names = {
            'prototype': 'Прототипы',
            'functional': 'Функциональные',
            'art': 'Художественные',
            'industrial': 'Промышленные'
        };
        return names[category] || category;
    }
    // ========================================
    // SERVICES MANAGEMENT
    // ========================================

    async loadServices() {
        await this.renderServices();
    }

    async renderServices() {
        const list = document.getElementById('servicesAdminList');
        if (!list) return;

        list.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--admin-text-secondary);"><i class="fas fa-spinner fa-spin"></i> Загрузка услуг...</div>';

        const result = await this.api.fetch('/api/admin/services');

        if (!result.success) {
            list.innerHTML = `<p style="text-align: center; padding: 40px; color: var(--admin-danger);">Ошибка загрузки: ${result.error}</p>`;
            return;
        }

        const services = result.data.data || [];

        if (services.length === 0) {
            list.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--admin-text-secondary);">Услуг пока нет</p>';
            return;
        }

        list.innerHTML = services.map(service => `
            <div class="service-admin-item" style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                            <i class="fas ${service.icon}"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0;">${service.name}</h3>
                            ${service.featured ? '<span class="status-badge status-new" style="font-size: 11px; margin-left: 10px;">Популярное</span>' : ''}
                        </div>
                    </div>
                    <p style="color: var(--admin-text-secondary); margin-bottom: 10px;">${service.description}</p>
                    <strong style="color: var(--admin-primary);">${service.price}</strong>
                    ${service.features && service.features.length > 0 ? `
                        <div style="margin-top: 10px; font-size: 13px; color: var(--admin-text-secondary);">
                            <strong>Возможности:</strong> ${service.features.map(f => typeof f === 'string' ? f : f.feature).join(', ')}
                        </div>
                    ` : ''}
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label class="toggle-switch">
                        <input type="checkbox" ${service.active ? 'checked' : ''} onchange="admin.toggleService(${service.id})">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="action-btns">
                        <button class="action-btn edit" onclick="admin.editService(${service.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="admin.deleteService(${service.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    addService() {
        const fields = [
            new FormField({
                name: 'name',
                label: 'Название услуги',
                type: 'text',
                required: true,
                placeholder: 'Например: FDM печать'
            }),
            new FormField({
                name: 'slug',
                label: 'Слаг (для URL)',
                type: 'text',
                required: false,
                placeholder: 'fdm-pechat (оставьте пустым для автогенерации)'
            }),
            new FormField({
                name: 'icon',
                label: 'Иконка (Font Awesome)',
                type: 'text',
                required: true,
                placeholder: 'fa-cube',
                helpText: 'Класс иконки без "fas", например: fa-cube'
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true
            }),
            new FormField({
                name: 'price',
                label: 'Цена',
                type: 'text',
                required: true,
                placeholder: 'от 50₽/г'
            }),
            new FormField({
                name: 'features',
                label: 'Возможности (через запятую)',
                type: 'textarea',
                placeholder: 'Быстрая печать, Высокая точность, Низкая стоимость',
                helpText: 'Перечислите возможности услуги через запятую'
            }),
            new FormField({
                name: 'featured',
                label: '',
                type: 'checkbox',
                placeholder: 'Отметить как популярное'
            })
        ];

        this.showFormModal('Добавить услугу', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            // Convert features from comma-separated string to array
            const features = formData.features 
                ? formData.features.split(',').map(f => f.trim()).filter(f => f)
                : [];

            const serviceData = {
                name: formData.name,
                slug: formData.slug || '',
                icon: formData.icon,
                description: formData.description,
                price: formData.price,
                features: features,
                featured: formData.featured || false,
                active: true
            };

            const result = await this.api.fetch('/api/admin/services', {
                method: 'POST',
                body: JSON.stringify(serviceData)
            });

            if (!result.success) {
                this.showNotification(`Ошибка: ${result.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Услуга добавлена', 'success');
            this.closeAllModals();
            await this.renderServices();
        });
    }

    async editService(id) {
        const result = await this.api.fetch(`/api/services/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки: ${result.error}`, 'error');
            return;
        }

        const service = result.data.data;
        const featuresStr = service.features 
            ? service.features.map(f => typeof f === 'string' ? f : f.feature).join(', ')
            : '';

        const fields = [
            new FormField({
                name: 'name',
                label: 'Название',
                type: 'text',
                required: true,
                value: service.name
            }),
            new FormField({
                name: 'slug',
                label: 'Слаг',
                type: 'text',
                required: false,
                value: service.slug
            }),
            new FormField({
                name: 'icon',
                label: 'Иконка',
                type: 'text',
                required: true,
                value: service.icon
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true,
                value: service.description
            }),
            new FormField({
                name: 'price',
                label: 'Цена',
                type: 'text',
                required: true,
                value: service.price
            }),
            new FormField({
                name: 'features',
                label: 'Возможности (через запятую)',
                type: 'textarea',
                value: featuresStr,
                helpText: 'Перечислите возможности услуги через запятую'
            }),
            new FormField({
                name: 'featured',
                label: '',
                type: 'checkbox',
                placeholder: 'Популярное',
                value: service.featured
            }),
            new FormField({
                name: 'active',
                label: '',
                type: 'checkbox',
                placeholder: 'Активна',
                value: service.active
            })
        ];

        this.showFormModal('Редактировать услугу', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            // Convert features from comma-separated string to array
            const features = formData.features 
                ? formData.features.split(',').map(f => f.trim()).filter(f => f)
                : [];

            const serviceData = {
                name: formData.name,
                slug: formData.slug || '',
                icon: formData.icon,
                description: formData.description,
                price: formData.price,
                features: features,
                featured: formData.featured || false,
                active: formData.active !== false
            };

            const updateResult = await this.api.fetch(`/api/admin/services/${id}`, {
                method: 'PUT',
                body: JSON.stringify(serviceData)
            });

            if (!updateResult.success) {
                this.showNotification(`Ошибка: ${updateResult.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Услуга обновлена', 'success');
            this.closeAllModals();
            await this.renderServices();
        });
    }

    async deleteService(id) {
        if (!confirm('Удалить эту услугу?')) return;

        const result = await this.api.fetch(`/api/admin/services/${id}`, {
            method: 'DELETE'
        });

        if (!result.success) {
            this.showNotification(`Ошибка удаления: ${result.error}`, 'error');
            return;
        }

        this.showNotification('Услуга удалена', 'success');
        await this.renderServices();
    }

    async toggleService(id) {
        const result = await this.api.fetch(`/api/services/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка: ${result.error}`, 'error');
            return;
        }

        const service = result.data.data;
        const newActive = !service.active;

        const updateResult = await this.api.fetch(`/api/admin/services/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ active: newActive })
        });

        if (!updateResult.success) {
            this.showNotification(`Ошибка: ${updateResult.error}`, 'error');
            return;
        }

        this.showNotification(`Услуга ${newActive ? 'активирована' : 'деактивирована'}`, 'success');
        await this.renderServices();
    }

    // ========================================
    // TESTIMONIALS MANAGEMENT
    // ========================================

    async loadTestimonials() {
        await this.renderTestimonials();
    }

    async renderTestimonials() {
        const grid = document.getElementById('testimonialsAdminGrid');
        if (!grid) return;

        grid.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--admin-text-secondary); grid-column: 1/-1;"><i class="fas fa-spinner fa-spin"></i> Загрузка отзывов...</div>';

        const result = await this.api.fetch('/api/admin/testimonials');

        if (!result.success) {
            grid.innerHTML = `<p style="text-align: center; padding: 40px; color: var(--admin-danger); grid-column: 1/-1;">Ошибка загрузки: ${result.error}</p>`;
            return;
        }

        const testimonials = result.data.data || [];

        if (testimonials.length === 0) {
            grid.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--admin-text-secondary); grid-column: 1/-1;">Отзывов пока нет</p>';
            return;
        }

        grid.innerHTML = testimonials.map(item => `
            <div class="testimonial-admin-item" style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <img src="${item.avatar_url}" alt="${item.name}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                    <div style="flex: 1;">
                        <h4 style="margin-bottom: 5px;">${item.name}</h4>
                        <p style="color: var(--admin-text-secondary); font-size: 14px; margin-bottom: 5px;">${item.position}</p>
                        <div style="color: #fbbf24;">
                            ${'★'.repeat(item.rating)}${'☆'.repeat(5 - item.rating)}
                        </div>
                    </div>
                </div>
                <p style="color: var(--admin-text-secondary); font-style: italic; margin-bottom: 15px; line-height: 1.6;">"${item.text}"</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--admin-border);">
                    <span class="status-badge ${item.approved ? 'status-completed' : 'status-new'}">
                        ${item.approved ? 'Опубликован' : 'На модерации'}
                    </span>
                    <div class="action-btns">
                        ${!item.approved ? `<button class="action-btn" style="background: rgba(16,185,129,0.1); color: var(--admin-success);" onclick="admin.approveTestimonial(${item.id})">
                            <i class="fas fa-check"></i>
                        </button>` : ''}
                        <button class="action-btn edit" onclick="admin.editTestimonial(${item.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="admin.deleteTestimonial(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    addTestimonial() {
        const fields = [
            new FormField({
                name: 'name',
                label: 'Имя клиента',
                type: 'text',
                required: true,
                placeholder: 'Иван Петров'
            }),
            new FormField({
                name: 'position',
                label: 'Должность/Компания',
                type: 'text',
                required: true,
                placeholder: 'Директор, Tech Solutions'
            }),
            new FormField({
                name: 'avatar_url',
                label: 'URL аватара',
                type: 'url',
                required: true,
                placeholder: 'https://...'
            }),
            new FormField({
                name: 'rating',
                label: 'Оценка',
                type: 'select',
                required: true,
                options: [
                    { value: '5', label: '5 звёзд' },
                    { value: '4', label: '4 звезды' },
                    { value: '3', label: '3 звезды' }
                ]
            }),
            new FormField({
                name: 'text',
                label: 'Текст отзыва',
                type: 'textarea',
                required: true
            }),
            new FormField({
                name: 'approved',
                label: '',
                type: 'checkbox',
                placeholder: 'Опубликовать сразу'
            })
        ];

        this.showFormModal('Добавить отзыв', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            const testimonialData = {
                name: formData.name,
                position: formData.position,
                avatar_url: formData.avatar_url,
                rating: parseInt(formData.rating),
                text: formData.text,
                approved: formData.approved || false
            };

            const result = await this.api.fetch('/api/admin/testimonials', {
                method: 'POST',
                body: JSON.stringify(testimonialData)
            });

            if (!result.success) {
                this.showNotification(`Ошибка: ${result.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Отзыв добавлен', 'success');
            this.closeAllModals();
            await this.renderTestimonials();
        });
    }

    async editTestimonial(id) {
        const result = await this.api.fetch(`/api/testimonials/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки: ${result.error}`, 'error');
            return;
        }

        const item = result.data.data;

        const fields = [
            new FormField({
                name: 'name',
                label: 'Имя',
                type: 'text',
                required: true,
                value: item.name
            }),
            new FormField({
                name: 'position',
                label: 'Должность',
                type: 'text',
                required: true,
                value: item.position
            }),
            new FormField({
                name: 'avatar_url',
                label: 'URL аватара',
                type: 'url',
                required: true,
                value: item.avatar_url
            }),
            new FormField({
                name: 'rating',
                label: 'Оценка',
                type: 'select',
                required: true,
                value: item.rating.toString(),
                options: [
                    { value: '5', label: '5 звёзд' },
                    { value: '4', label: '4 звезды' },
                    { value: '3', label: '3 звезды' }
                ]
            }),
            new FormField({
                name: 'text',
                label: 'Текст',
                type: 'textarea',
                required: true,
                value: item.text
            }),
            new FormField({
                name: 'approved',
                label: '',
                type: 'checkbox',
                placeholder: 'Опубликован',
                value: item.approved
            })
        ];

        this.showFormModal('Редактировать отзыв', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            const testimonialData = {
                name: formData.name,
                position: formData.position,
                avatar_url: formData.avatar_url,
                rating: parseInt(formData.rating),
                text: formData.text,
                approved: formData.approved !== false
            };

            const updateResult = await this.api.fetch(`/api/admin/testimonials/${id}`, {
                method: 'PUT',
                body: JSON.stringify(testimonialData)
            });

            if (!updateResult.success) {
                this.showNotification(`Ошибка: ${updateResult.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Отзыв обновлён', 'success');
            this.closeAllModals();
            await this.renderTestimonials();
        });
    }

    async deleteTestimonial(id) {
        if (!confirm('Удалить этот отзыв?')) return;

        const result = await this.api.fetch(`/api/admin/testimonials/${id}`, {
            method: 'DELETE'
        });

        if (!result.success) {
            this.showNotification(`Ошибка удаления: ${result.error}`, 'error');
            return;
        }

        this.showNotification('Отзыв удалён', 'success');
        await this.renderTestimonials();
    }

    async approveTestimonial(id) {
        const result = await this.api.fetch(`/api/admin/testimonials/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ approved: true })
        });

        if (!result.success) {
            this.showNotification(`Ошибка: ${result.error}`, 'error');
            return;
        }

        this.showNotification('Отзыв одобрен', 'success');
        await this.renderTestimonials();
    }

    // ========================================
    // FAQ MANAGEMENT
    // ========================================

    async loadFAQ() {
        await this.renderFAQ();
    }

    async renderFAQ() {
        const list = document.getElementById('faqAdminList');
        if (!list) return;

        list.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--admin-text-secondary);"><i class="fas fa-spinner fa-spin"></i> Загрузка FAQ...</div>';

        const result = await this.api.fetch('/api/admin/faq');

        if (!result.success) {
            list.innerHTML = `<p style="text-align: center; padding: 40px; color: var(--admin-danger);">Ошибка загрузки: ${result.error}</p>`;
            return;
        }

        const items = result.data.data || [];

        if (items.length === 0) {
            list.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--admin-text-secondary);">Вопросов пока нет</p>';
            return;
        }

        list.innerHTML = items.map(item => `
            <div class="faq-admin-item" style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px;">
                    <div style="flex: 1;">
                        <h4 style="margin-bottom: 10px; color: var(--admin-primary);">
                            <i class="fas fa-question-circle"></i> ${item.question}
                        </h4>
                        <p style="color: var(--admin-text-secondary); line-height: 1.6;">${item.answer}</p>
                        <div style="margin-top: 10px; display: flex; align-items: center; gap: 15px;">
                            <span class="status-badge ${item.active ? 'status-completed' : 'status-cancelled'}">
                                ${item.active ? 'Активен' : 'Неактивен'}
                            </span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label class="toggle-switch">
                            <input type="checkbox" ${item.active ? 'checked' : ''} onchange="admin.toggleFAQ(${item.id})">
                            <span class="toggle-slider"></span>
                        </label>
                        <div class="action-btns">
                            <button class="action-btn edit" onclick="admin.editFAQ(${item.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete" onclick="admin.deleteFAQ(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    addFAQ() {
        const fields = [
            new FormField({
                name: 'question',
                label: 'Вопрос',
                type: 'text',
                required: true,
                placeholder: 'Например: Какие материалы вы используете?'
            }),
            new FormField({
                name: 'answer',
                label: 'Ответ',
                type: 'textarea',
                required: true,
                placeholder: 'Подробный ответ на вопрос...'
            }),
            new FormField({
                name: 'active',
                label: '',
                type: 'checkbox',
                placeholder: 'Опубликовать',
                value: true
            })
        ];

        this.showFormModal('Добавить вопрос в FAQ', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            const result = await this.api.fetch('/api/admin/faq', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (!result.success) {
                this.showNotification(`Ошибка: ${result.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Вопрос добавлен', 'success');
            this.closeAllModals();
            await this.renderFAQ();
        });
    }

    async editFAQ(id) {
        const result = await this.api.fetch(`/api/faq/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки: ${result.error}`, 'error');
            return;
        }

        const item = result.data.data;

        const fields = [
            new FormField({
                name: 'question',
                label: 'Вопрос',
                type: 'text',
                required: true,
                value: item.question
            }),
            new FormField({
                name: 'answer',
                label: 'Ответ',
                type: 'textarea',
                required: true,
                value: item.answer
            }),
            new FormField({
                name: 'active',
                label: '',
                type: 'checkbox',
                placeholder: 'Активен',
                value: item.active
            })
        ];

        this.showFormModal('Редактировать вопрос FAQ', fields, async (formData) => {
            const submitBtn = document.querySelector('.modal button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }

            const updateResult = await this.api.fetch(`/api/admin/faq/${id}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });

            if (!updateResult.success) {
                this.showNotification(`Ошибка: ${updateResult.error}`, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                }
                return;
            }

            this.showNotification('Вопрос обновлён', 'success');
            this.closeAllModals();
            await this.renderFAQ();
        });
    }

    async deleteFAQ(id) {
        if (!confirm('Удалить этот вопрос из FAQ?')) return;

        const result = await this.api.fetch(`/api/admin/faq/${id}`, {
            method: 'DELETE'
        });

        if (!result.success) {
            this.showNotification(`Ошибка удаления: ${result.error}`, 'error');
            return;
        }

        this.showNotification('Вопрос удалён', 'success');
        await this.renderFAQ();
    }

    async toggleFAQ(id) {
        const result = await this.api.fetch(`/api/faq/${id}`);
        
        if (!result.success) {
            this.showNotification(`Ошибка: ${result.error}`, 'error');
            return;
        }

        const item = result.data.data;
        const newActive = !item.active;

        const updateResult = await this.api.fetch(`/api/admin/faq/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ active: newActive })
        });

        if (!updateResult.success) {
            this.showNotification(`Ошибка: ${updateResult.error}`, 'error');
            return;
        }

        this.showNotification(`Вопрос ${newActive ? 'активирован' : 'деактивирован'}`, 'success');
        await this.renderFAQ();
    }

    // ========================================
    // CALCULATOR SETTINGS (Backend API Integration)
    // ========================================

    async loadCalculatorSettings() {
        console.log('⚙️ Загрузка настроек калькулятора...');

        // Show loading state
        const container = document.getElementById('materialPrices');
        if (container) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--admin-text-secondary);"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';
        }

        const result = await this.api.fetch('/api/settings');
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки настроек: ${result.error}`, 'error');
            return;
        }

        const settings = result.data.data;

        // Store settings for rendering
        this.currentSettings = settings;

        // Update CONFIG with loaded settings
        if (settings.calculator) {
            if (settings.calculator.materials) {
                CONFIG.materialPrices = this.transformMaterialsFromAPI(settings.calculator.materials);
            }
            if (settings.calculator.services) {
                CONFIG.servicePrices = this.transformServicesFromAPI(settings.calculator.services);
            }
            if (settings.calculator.quality_levels) {
                CONFIG.qualityMultipliers = this.transformQualityFromAPI(settings.calculator.quality_levels);
            }
            if (settings.calculator.volume_discounts) {
                CONFIG.discounts = this.transformDiscountsFromAPI(settings.calculator.volume_discounts);
            }
        }

        this.renderMaterialPrices();
        this.renderServicePrices();
        this.renderDiscounts();
        this.renderQualityMultipliers();
    }

    transformMaterialsFromAPI(materials) {
        const result = {};
        materials.forEach(mat => {
            result[mat.material_key] = {
                name: mat.name,
                price: mat.price,
                technology: mat.technology
            };
        });
        return result;
    }

    transformServicesFromAPI(services) {
        const result = {};
        services.forEach(svc => {
            result[svc.service_key] = {
                name: svc.name,
                price: svc.price,
                unit: svc.unit
            };
        });
        return result;
    }

    transformQualityFromAPI(qualityLevels) {
        const result = {};
        qualityLevels.forEach(ql => {
            result[ql.quality_key] = {
                name: ql.name,
                multiplier: ql.price_multiplier,
                time: ql.time_multiplier
            };
        });
        return result;
    }

    transformDiscountsFromAPI(discounts) {
        return discounts.map(d => ({
            minQuantity: d.min_quantity,
            percent: d.discount_percent
        }));
    }

    renderMaterialPrices() {
        const materials = CONFIG.materialPrices;
        const container = document.getElementById('materialPrices');
        if (!container) return;

        container.innerHTML = Object.entries(materials).map(([key, mat]) => `
            <div class="price-input-group">
                <label>${mat.name} (₽/г) <small style="color: var(--admin-text-secondary);">${mat.technology.toUpperCase()}</small></label>
                <input type="number" 
                       value="${mat.price}" 
                       class="form-control" 
                       data-material="${key}"
                       min="0"
                       step="1">
            </div>
        `).join('');
    }

    renderServicePrices() {
        const prices = CONFIG.servicePrices;

        if (document.getElementById('modelingPrice')) {
            document.getElementById('modelingPrice').value = prices.modeling.price;
        }
        if (document.getElementById('postProcessingPrice')) {
            document.getElementById('postProcessingPrice').value = prices.postProcessing.price;
        }
        if (document.getElementById('paintingPrice')) {
            document.getElementById('paintingPrice').value = prices.painting.price;
        }
        if (document.getElementById('expressPrice')) {
            document.getElementById('expressPrice').value = prices.express.price;
        }
    }

    renderDiscounts() {
        const discounts = CONFIG.discounts;

        if (document.getElementById('discount10')) {
            const discount10 = discounts.find(d => d.minQuantity === 10);
            document.getElementById('discount10').value = discount10 ? discount10.percent : 10;
        }
        if (document.getElementById('discount50')) {
            const discount50 = discounts.find(d => d.minQuantity === 50);
            document.getElementById('discount50').value = discount50 ? discount50.percent : 15;
        }
        if (document.getElementById('discount100')) {
            const discount100 = discounts.find(d => d.minQuantity === 100);
            document.getElementById('discount100').value = discount100 ? discount100.percent : 20;
        }
    }

    renderQualityMultipliers() {
        const quality = CONFIG.qualityMultipliers;

        if (document.getElementById('qualityDraft')) {
            document.getElementById('qualityDraft').value = quality.draft.multiplier;
        }
        if (document.getElementById('qualityNormal')) {
            document.getElementById('qualityNormal').value = quality.normal.multiplier;
        }
        if (document.getElementById('qualityHigh')) {
            document.getElementById('qualityHigh').value = quality.high.multiplier;
        }
        if (document.getElementById('qualityUltra')) {
            document.getElementById('qualityUltra').value = quality.ultra.multiplier;
        }
    }

    async saveCalculatorSettings() {
        console.log('💾 Сохранение настроек калькулятора...');

        const saveBtn = document.querySelector('#page-calculator .btn-primary');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        }

        // Collect material prices
        const materialInputs = document.querySelectorAll('#materialPrices input');
        materialInputs.forEach(input => {
            const key = input.getAttribute('data-material');
            const value = parseFloat(input.value);
            if (key && !isNaN(value)) {
                CONFIG.materialPrices[key].price = value;
            }
        });

        // Collect service prices
        CONFIG.servicePrices.modeling.price = parseFloat(document.getElementById('modelingPrice').value) || 500;
        CONFIG.servicePrices.postProcessing.price = parseFloat(document.getElementById('postProcessingPrice').value) || 300;
        CONFIG.servicePrices.painting.price = parseFloat(document.getElementById('paintingPrice').value) || 500;
        CONFIG.servicePrices.express.price = parseFloat(document.getElementById('expressPrice').value) || 1000;

        // Collect discounts
        CONFIG.discounts = [
            { minQuantity: 10, percent: parseFloat(document.getElementById('discount10')?.value || 10) },
            { minQuantity: 50, percent: parseFloat(document.getElementById('discount50')?.value || 15) },
            { minQuantity: 100, percent: parseFloat(document.getElementById('discount100')?.value || 20) }
        ];

        // Collect quality multipliers
        CONFIG.qualityMultipliers.draft.multiplier = parseFloat(document.getElementById('qualityDraft')?.value || 0.8);
        CONFIG.qualityMultipliers.normal.multiplier = parseFloat(document.getElementById('qualityNormal')?.value || 1.0);
        CONFIG.qualityMultipliers.high.multiplier = parseFloat(document.getElementById('qualityHigh')?.value || 1.3);
        CONFIG.qualityMultipliers.ultra.multiplier = parseFloat(document.getElementById('qualityUltra')?.value || 1.6);

        // Transform to backend format
        const payload = {
            materials: Object.entries(CONFIG.materialPrices).map(([key, mat]) => ({
                material_key: key,
                name: mat.name,
                price: mat.price,
                technology: mat.technology
            })),
            services: Object.entries(CONFIG.servicePrices).map(([key, svc]) => ({
                service_key: key,
                name: svc.name,
                price: svc.price,
                unit: svc.unit
            })),
            quality_levels: Object.entries(CONFIG.qualityMultipliers).map(([key, ql]) => ({
                quality_key: key,
                name: ql.name,
                price_multiplier: ql.multiplier,
                time_multiplier: ql.time || 1.0
            })),
            volume_discounts: CONFIG.discounts.map(d => ({
                min_quantity: d.minQuantity,
                discount_percent: d.percent
            }))
        };

        console.log('Отправка настроек калькулятора:', payload);

        const result = await this.api.fetch('/api/settings/calculator', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });

        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить изменения';
        }

        if (!result.success) {
            const errorMsg = result.data?.errors 
                ? Object.values(result.data.errors).flat().join(', ') 
                : result.error || 'Ошибка сохранения настроек';
            this.showNotification(`Ошибка: ${errorMsg}`, 'error');
            return;
        }

        console.log('✅ Настройки калькулятора сохранены в БД');

        this.showNotification('✅ Настройки калькулятора сохранены!', 'success');

        // Instruction to refresh main site
        const updateMessage = `✅ Настройки сохранены!

Для применения изменений на главной странице:
1. Откройте главную страницу (index.html)
2. Обновите страницу (F5 или Ctrl+R)
3. Проверьте калькулятор

Хотите открыть главную страницу сейчас?`;

        if (confirm(updateMessage)) {
            window.open('index.html', '_blank');
        }
    }

    // ========================================
    // CONTENT MANAGEMENT (ИСПРАВЛЕНО #11)
    // ========================================

    loadContent() {
        console.log('📄 Загрузка контента...');

        const content = db.getOrCreateContent();
        const settings = db.getOrCreateSettings();

        console.log('Content:', content);
        console.log('Settings:', settings);

        // Hero
        if (document.getElementById('heroTitle')) {
            document.getElementById('heroTitle').value = content.hero?.title || '';
        }
        if (document.getElementById('heroDescription')) {
            document.getElementById('heroDescription').value = content.hero?.subtitle || '';
        }

        // Contact info
        if (document.getElementById('contentAddress')) {
            document.getElementById('contentAddress').value = settings.address || '';
        }
        if (document.getElementById('contentPhone')) {
            document.getElementById('contentPhone').value = settings.contactPhone || '';
        }
        if (document.getElementById('contentEmail')) {
            document.getElementById('contentEmail').value = settings.contactEmail || '';
        }
        if (document.getElementById('contentWorkingHours')) {
            document.getElementById('contentWorkingHours').value = settings.workingHours || '';
        }

        // Social
        if (settings.socialLinks) {
            if (document.getElementById('socialVk')) {
                document.getElementById('socialVk').value = settings.socialLinks.vk || '';
            }
            if (document.getElementById('socialTelegram')) {
                document.getElementById('socialTelegram').value = settings.socialLinks.telegram || 'https://t.me/PrintPro_Omsk';
            }
            if (document.getElementById('socialWhatsapp')) {
                document.getElementById('socialWhatsapp').value = settings.socialLinks.whatsapp || '';
            }
            if (document.getElementById('socialYoutube')) {
                document.getElementById('socialYoutube').value = settings.socialLinks.youtube || '';
            }
        }

        this.loadFAQEditor();
    }

    loadFAQEditor() {
        const faqs = db.getData('faq') || [];
        const container = document.getElementById('faqEditor');
        if (!container) return;

        container.innerHTML = faqs.map((faq, index) => `
            <div class="faq-editor-item" style="background: var(--admin-bg); padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <div class="form-group">
                            <label>Вопрос</label>
                            <input type="text" class="form-control" value="${faq.question}" data-faq-id="${faq.id}" data-field="question">
                        </div>
                        <div class="form-group">
                            <label>Ответ</label>
                            <textarea class="form-control" rows="3" data-faq-id="${faq.id}" data-field="answer">${faq.answer}</textarea>
                        </div>
                        <label class="checkbox-label">
                            <input type="checkbox" ${faq.active ? 'checked' : ''} data-faq-id="${faq.id}" data-field="active">
                            <span>Активен</span>
                        </label>
                    </div>
                    <div class="action-btns" style="flex-direction: column;">
                        <button class="action-btn" onclick="admin.moveFAQ('${faq.id}', 'up')">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button class="action-btn" onclick="admin.moveFAQ('${faq.id}', 'down')">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button class="action-btn delete" onclick="admin.deleteFAQ('${faq.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Add change listeners
        container.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('change', (e) => {
                const id = e.target.getAttribute('data-faq-id');
                const fieldName = e.target.getAttribute('data-field');
                const value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;

                db.updateItem('faq', id, { [fieldName]: value });
            });
        });
    }

    addFAQItem() {
        const fields = [
            new FormField({
                name: 'question',
                label: 'Вопрос',
                type: 'text',
                required: true
            }),
            new FormField({
                name: 'answer',
                label: 'Ответ',
                type: 'textarea',
                required: true
            }),
            new FormField({
                name: 'active',
                label: '',
                type: 'checkbox',
                placeholder: 'Активен',
                value: true
            })
        ];

        this.showFormModal('Добавить вопрос', fields, (formData) => {
            const faq = {
                ...formData,
                order: (db.getData('faq') || []).length + 1
            };

            db.addItem('faq', faq);
            this.showNotification('Вопрос добавлен', 'success');
            this.loadFAQEditor();
        });
    }

    deleteFAQ(id) {
        if (!confirm('Удалить этот вопрос?')) return;

        db.deleteItem('faq', id);
        this.showNotification('Вопрос удалён', 'success');
        this.loadFAQEditor();
    }

    moveFAQ(id, direction) {
        const items = db.getData('faq') || [];
        const index = items.findIndex(f => f.id === id);

        if (index === -1) return;

        if (direction === 'up' && index > 0) {
            [items[index], items[index - 1]] = [items[index - 1], items[index]];
        } else if (direction === 'down' && index < items.length - 1) {
            [items[index], items[index + 1]] = [items[index + 1], items[index]];
        } else {
            return;
        }

        items.forEach((item, i) => {
            item.order = i + 1;
        });

        db.saveData('faq', items);
        this.loadFAQEditor();
    }

    saveContent() {
        console.log('💾 Сохранение контента...');

        const contentData = {
            hero: {
                title: document.getElementById('heroTitle')?.value || '',
                subtitle: document.getElementById('heroDescription')?.value || ''
            }
        };

        const settingsData = {
            address: document.getElementById('contentAddress')?.value || '',
            contactPhone: document.getElementById('contentPhone')?.value || '',
            contactEmail: document.getElementById('contentEmail')?.value || '',
            workingHours: document.getElementById('contentWorkingHours')?.value || '',
            socialLinks: {
                vk: document.getElementById('socialVk')?.value || '',
                telegram: document.getElementById('socialTelegram')?.value || 'https://t.me/PrintPro_Omsk',
                whatsapp: document.getElementById('socialWhatsapp')?.value || '',
                youtube: document.getElementById('socialYoutube')?.value || ''
            }
        };

        console.log('Content data:', contentData);
        console.log('Settings data:', settingsData);

        // Сохраняем через новые методы
        db.updateContent(contentData);
        db.updateSettings(settingsData);

        console.log('✅ Контент сохранён в БД');

        this.showNotification('✅ Контент успешно сохранён', 'success');
    }

    // ========================================
    // FORMS MANAGEMENT (Backend API Integration)
    // ========================================

    async loadFormSettings() {
        console.log('📝 Загрузка настроек форм...');

        // Show loading state
        const container = document.getElementById('contactFormFields');
        if (container) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--admin-text-secondary);"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';
        }

        const result = await this.api.fetch('/api/settings');
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки настроек форм: ${result.error}`, 'error');
            return;
        }

        const settings = result.data.data;
        this.currentSettings = settings;

        if (settings.forms && settings.forms.fields) {
            CONFIG.formFields = this.transformFormFieldsFromAPI(settings.forms.fields);
        }

        this.renderFormFields('contact');
        await this.loadTelegramSettings();
    }

    transformFormFieldsFromAPI(fields) {
        const grouped = { contact: [], order: [] };
        fields.forEach(field => {
            const formType = field.form_type || 'contact';
            if (!grouped[formType]) grouped[formType] = [];
            
            grouped[formType].push({
                name: field.field_name,
                label: field.label,
                type: field.field_type,
                required: field.required,
                enabled: field.enabled,
                placeholder: field.placeholder,
                options: field.options || [],
                order: field.display_order || 0
            });
        });
        
        // Sort by display order
        grouped.contact.sort((a, b) => (a.order || 0) - (b.order || 0));
        grouped.order.sort((a, b) => (a.order || 0) - (b.order || 0));
        
        return grouped;
    }

    renderFormFields(formType) {
        const fields = CONFIG.formFields[formType] || [];
        const container = document.getElementById(`${formType}FormFields`);
        if (!container) return;

        // Сортируем по order
        const sortedFields = [...fields].sort((a, b) => (a.order || 0) - (b.order || 0));

        container.innerHTML = sortedFields.map((field, index) => `
        <div class="form-field-item" style="padding: 20px; background: ${field.enabled ? 'var(--admin-bg)' : 'rgba(239,68,68,0.05)'}; border-radius: 10px; margin-bottom: 15px; border: 2px solid ${field.enabled ? 'var(--admin-border)' : 'rgba(239,68,68,0.2)'};">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 15px;">
                <div style="flex: 1; ${!field.enabled ? 'opacity: 0.5;' : ''}">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-size: 13px; color: var(--admin-text-secondary); font-weight: 600;">Название поля</label>
                        <input type="text" class="form-control" value="${field.label}" 
                               onchange="admin.updateFormField('${formType}', ${index}, 'label', this.value)"
                               style="font-weight: 500;">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-size: 13px; color: var(--admin-text-secondary);">Тип поля</label>
                            <select class="form-control" onchange="admin.updateFormField('${formType}', ${index}, 'type', this.value)">
                                <option value="text" ${field.type === 'text' ? 'selected' : ''}>Текст</option>
                                <option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
                                <option value="tel" ${field.type === 'tel' ? 'selected' : ''}>Телефон</option>
                                <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Текст (многострочный)</option>
                                <option value="select" ${field.type === 'select' ? 'selected' : ''}>Выбор из списка</option>
                                <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Чекбокс</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-size: 13px; color: var(--admin-text-secondary);">Атрибут name</label>
                            <input type="text" class="form-control" value="${field.name}" readonly 
                                   style="background: var(--admin-bg); cursor: not-allowed; font-family: monospace; font-size: 12px;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-size: 13px; color: var(--admin-text-secondary);">Порядок</label>
                            <input type="number" class="form-control" value="${field.order || index + 1}" 
                                   onchange="admin.updateFormField('${formType}', ${index}, 'order', parseInt(this.value))"
                                   min="1" max="100">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size: 13px; color: var(--admin-text-secondary);">Placeholder / Подсказка</label>
                        <input type="text" class="form-control" value="${field.placeholder || ''}" 
                               onchange="admin.updateFormField('${formType}', ${index}, 'placeholder', this.value)"
                               placeholder="Введите подсказку...">
                    </div>
                    
                    ${field.type === 'select' ? `
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size: 13px; color: var(--admin-text-secondary);">Варианты для выбора (через запятую)</label>
                        <input type="text" class="form-control" value="${Array.isArray(field.options) ? field.options.join(', ') : ''}" 
                               onchange="admin.updateFormFieldOptions('${formType}', ${index}, this.value)"
                               placeholder="Вариант 1, Вариант 2, Вариант 3">
                    </div>
                    ` : ''}
                    
                    <div style="display: flex; gap: 20px; align-items: center; margin-top: 10px;">
    <label class="checkbox-label" style="margin: 0;">
        <input type="checkbox" ${field.required ? 'checked' : ''} 
               ${['name', 'email', 'phone', 'message'].includes(field.name) ? 'disabled' : ''}
               onchange="admin.updateFormField('${formType}', ${index}, 'required', this.checked)">
        <span ${['name', 'email', 'phone', 'message'].includes(field.name) ? 'style="opacity: 0.5;" title="Системное поле"' : ''}>
            Обязательное поле ${field.required ? '✓' : ''}
        </span>
    </label>
    <label class="checkbox-label" style="margin: 0;">
        <input type="checkbox" ${field.enabled ? 'checked' : ''} 
               onchange="admin.updateFormField('${formType}', ${index}, 'enabled', this.checked)">
        <span style="font-weight: 600; color: ${field.enabled ? 'var(--admin-success)' : 'var(--admin-danger)'};">
            ${field.enabled ? '✓ Активно' : '✗ Отключено'}
        </span>
    </label>
</div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 5px; flex-shrink: 0;">
                    <button class="action-btn" onclick="admin.moveFormField('${formType}', ${index}, 'up')" title="Вверх">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button class="action-btn" onclick="admin.moveFormField('${formType}', ${index}, 'down')" title="Вниз">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button class="action-btn delete" onclick="admin.deleteFormField('${formType}', ${index})" title="Удалить поле">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    }
    updateFormField(formType, index, property, value) {
        if (!CONFIG.formFields[formType][index]) return;

        const field = CONFIG.formFields[formType][index];

        // Protection for system fields
        if (property === 'required') {
            if (['name', 'email', 'phone', 'message'].includes(field.name) && value === false) {
                this.showNotification('⚠️ Это системное поле должно оставаться обязательным', 'warning');
                this.renderFormFields(formType);
                return;
            }
        }

        CONFIG.formFields[formType][index][property] = value;

        console.log(`✏️ Обновлено поле ${formType}[${index}].${property} = ${value}`);

        // Note: Changes are saved when user clicks "Save" button
        // No auto-save to prevent excessive API calls
        this.renderFormFields(formType);
    }
    updateFormFieldOptions(formType, index, optionsString) {
        if (!CONFIG.formFields[formType][index]) return;

        const options = optionsString.split(',').map(opt => opt.trim()).filter(opt => opt);
        CONFIG.formFields[formType][index].options = options;

        console.log(`✏️ Обновлены опции для ${formType}[${index}]:`, options);
        this.renderFormFields(formType);
    }

    moveFormField(formType, index, direction) {
        const fields = CONFIG.formFields[formType];

        if (direction === 'up' && index > 0) {
            [fields[index], fields[index - 1]] = [fields[index - 1], fields[index]];
        } else if (direction === 'down' && index < fields.length - 1) {
            [fields[index], fields[index + 1]] = [fields[index + 1], fields[index]];
        } else {
            return;
        }

        // Update order
        fields.forEach((field, i) => {
            field.order = i + 1;
        });

        this.renderFormFields(formType);
    }

    async saveFormSettings() {
        console.log('💾 Сохранение настроек форм...');

        const saveBtn = document.querySelector('#page-forms .btn-primary');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        }

        // Transform form fields to backend format
        const fields = [];
        Object.entries(CONFIG.formFields).forEach(([formType, formFields]) => {
            formFields.forEach((field, index) => {
                fields.push({
                    form_type: formType,
                    field_name: field.name,
                    label: field.label,
                    field_type: field.type,
                    required: field.required,
                    enabled: field.enabled,
                    placeholder: field.placeholder || '',
                    options: field.options || [],
                    display_order: field.order || index + 1
                });
            });
        });

        const payload = { fields };

        console.log('Отправка настроек форм:', payload);

        const result = await this.api.fetch('/api/settings/forms', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });

        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить и применить';
        }

        if (!result.success) {
            const errorMsg = result.data?.errors 
                ? Object.values(result.data.errors).flat().join(', ') 
                : result.error || 'Ошибка сохранения настроек';
            this.showNotification(`Ошибка: ${errorMsg}`, 'error');
            return;
        }

        console.log('✅ Настройки форм сохранены в БД');

        this.showNotification('✅ Настройки форм сохранены!', 'success');

        // Instruction to refresh main site
        if (confirm('Настройки сохранены!\n\nОткрыть главную страницу для проверки изменений?')) {
            window.open('index.html', '_blank');
        }
    }
    addFormField(formType) {
        const newField = {
            name: 'custom_field_' + Date.now(),
            label: 'Новое поле',
            type: 'text',
            required: false,
            enabled: true,
            placeholder: 'Введите значение...',
            order: CONFIG.formFields[formType].length + 1
        };

        CONFIG.formFields[formType].push(newField);

        this.showNotification('✅ Поле добавлено. Не забудьте сохранить изменения.', 'success');
        this.renderFormFields(formType);
    }

    deleteFormField(formType, index) {
        const field = CONFIG.formFields[formType][index];

        // Protection for system fields
        if (['name', 'email', 'phone', 'message'].includes(field.name)) {
            if (!confirm(`⚠️ ВНИМАНИЕ!\n\nВы удаляете системное поле "${field.label}".\nЭто может нарушить работу формы.\n\nПродолжить?`)) {
                return;
            }
        } else {
            if (!confirm(`Удалить поле "${field.label}"?`)) {
                return;
            }
        }

        CONFIG.formFields[formType].splice(index, 1);

        // Update order
        CONFIG.formFields[formType].forEach((f, i) => {
            f.order = i + 1;
        });

        this.showNotification('✅ Поле удалено. Не забудьте сохранить изменения.', 'success');
        this.renderFormFields(formType);
    }

    // ========================================
    // TELEGRAM SETTINGS (Backend API Integration)
    // ========================================

    async loadTelegramSettings() {
        if (!this.currentSettings) {
            const result = await this.api.fetch('/api/settings');
            if (result.success) {
                this.currentSettings = result.data.data;
            }
        }

        const settings = this.currentSettings;
        const chatIdInput = document.getElementById('telegramChatId');
        const notifToggle = document.getElementById('telegramNotifications');

        if (chatIdInput && settings?.integrations?.telegram) {
            chatIdInput.value = settings.integrations.telegram.chat_id || '';
        }

        if (notifToggle && settings?.site) {
            notifToggle.checked = settings.site.notifications_enabled !== undefined
                ? settings.site.notifications_enabled
                : true;
        }

        // Load and display Telegram status
        await this.loadTelegramStatus();
    }

    async loadTelegramStatus() {
        const result = await this.api.fetch('/api/telegram/status');
        
        if (result.success && result.data.data) {
            const status = result.data.data;
            const statusContainer = document.getElementById('telegramStatus');
            
            if (statusContainer) {
                let statusHTML = '<div style="padding: 15px; background: var(--admin-bg); border-radius: 10px; margin-bottom: 15px;">';
                statusHTML += '<strong style="display: block; margin-bottom: 10px;">Статус интеграции:</strong>';
                
                if (status.connected) {
                    statusHTML += '<div style="color: var(--admin-success);"><i class="fas fa-check-circle"></i> Подключено</div>';
                    if (status.bot) {
                        statusHTML += `<small style="color: var(--admin-text-secondary);">Бот: ${status.bot.username || status.bot.first_name}</small>`;
                    }
                } else if (status.configured) {
                    statusHTML += '<div style="color: var(--admin-warning);"><i class="fas fa-exclamation-triangle"></i> Настроено, но не подключено</div>';
                    if (status.error) {
                        statusHTML += `<small style="color: var(--admin-danger);">${status.error}</small>`;
                    }
                } else {
                    statusHTML += '<div style="color: var(--admin-text-secondary);"><i class="fas fa-info-circle"></i> Не настроено</div>';
                }
                
                statusHTML += '</div>';
                statusContainer.innerHTML = statusHTML;
            }
        }
    }

    async getTelegramChatId() {
        this.showNotification('🔄 Получение Chat ID из Telegram...', 'info');

        const result = await this.api.fetch('/api/telegram/chat-id');

        if (!result.success) {
            this.showNotification(`❌ Ошибка: ${result.error}`, 'error');
            return;
        }

        const data = result.data.data;
        
        if (data.chat_ids && data.chat_ids.length > 0) {
            const chatId = data.chat_ids[0].id;
            const chatIdInput = document.getElementById('telegramChatId');
            
            if (chatIdInput) {
                chatIdInput.value = chatId;
            }

            let message = `✅ Найдено чатов: ${data.count}\n\n`;
            data.chat_ids.forEach((chat, index) => {
                message += `${index + 1}. ${chat.type}: ${chat.id}`;
                if (chat.title) message += ` (${chat.title})`;
                if (chat.username) message += ` (@${chat.username})`;
                message += '\n';
            });
            message += '\nПервый Chat ID добавлен в поле. Сохраните настройки.';

            this.showNotification(message, 'success');
        } else {
            this.showNotification(data.message || '⚠️ Chat ID не найдены. Отправьте сообщение боту и попробуйте снова.', 'warning');
        }
    }

    async testTelegramConnection() {
        const chatId = document.getElementById('telegramChatId')?.value;

        if (!chatId) {
            this.showNotification('⚠️ Сначала сохраните Chat ID', 'warning');
            return;
        }

        this.showNotification('📤 Отправка тестового сообщения...', 'info');

        const result = await this.api.fetch('/api/telegram/test', {
            method: 'POST'
        });

        if (!result.success) {
            this.showNotification(`❌ Ошибка отправки: ${result.error}`, 'error');
            return;
        }

        this.showNotification('✅ Тестовое сообщение успешно отправлено! Проверьте Telegram.', 'success');
        await this.loadTelegramStatus();
    }

    async saveTelegramSettings() {
        const chatId = document.getElementById('telegramChatId')?.value;
        const notifEnabled = document.getElementById('telegramNotifications')?.checked;

        if (!chatId) {
            this.showNotification('⚠️ Введите Chat ID', 'warning');
            return;
        }

        console.log('💾 Сохранение Telegram настроек...', { chatId, notifEnabled });

        const saveBtn = event?.target;
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        }

        const payload = {
            chat_id: chatId
        };

        const result = await this.api.fetch('/api/settings/telegram', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });

        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
        }

        if (!result.success) {
            const errorMsg = result.data?.errors 
                ? Object.values(result.data.errors).flat().join(', ') 
                : result.error || 'Ошибка сохранения настроек';
            this.showNotification(`Ошибка: ${errorMsg}`, 'error');
            return;
        }

        // Also update notification settings via general settings if needed
        if (notifEnabled !== undefined) {
            await this.api.fetch('/api/settings', {
                method: 'PUT',
                body: JSON.stringify({ notifications_enabled: notifEnabled })
            });
        }

        console.log('✅ Telegram настройки сохранены');

        this.showNotification('✅ Настройки Telegram сохранены', 'success');
        await this.loadTelegramStatus();
    }
    // ========================================
    // GENERAL SETTINGS (Backend API Integration)
    // ========================================

    async loadSettings() {
        console.log('⚙️ Загрузка общих настроек...');

        const result = await this.api.fetch('/api/settings');
        
        if (!result.success) {
            this.showNotification(`Ошибка загрузки настроек: ${result.error}`, 'error');
            return;
        }

        const settings = result.data.data;
        this.currentSettings = settings;

        console.log('Settings:', settings);

        // Site settings
        if (document.getElementById('settingsSiteName') && settings.site) {
            document.getElementById('settingsSiteName').value = settings.site.site_name || '3D Print Pro';
        }
        if (document.getElementById('settingsAdminEmail') && settings.site) {
            document.getElementById('settingsAdminEmail').value = settings.site.contact_email || '';
        }
        if (document.getElementById('settingsTimezone') && settings.site) {
            document.getElementById('settingsTimezone').value = settings.site.timezone || 'Europe/Moscow';
        }

        // Color settings
        if (document.getElementById('colorPrimary') && settings.site) {
            document.getElementById('colorPrimary').value = settings.site.color_primary || '#6366f1';
        }
        if (document.getElementById('colorSecondary') && settings.site) {
            document.getElementById('colorSecondary').value = settings.site.color_secondary || '#ec4899';
        }
    }

    async saveGeneralSettings() {
        console.log('💾 Сохранение общих настроек...');

        const saveBtn = document.querySelector('#page-settings .btn-primary');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        }

        const payload = {
            site_name: document.getElementById('settingsSiteName')?.value || '3D Print Pro',
            contact_email: document.getElementById('settingsAdminEmail')?.value || '',
            timezone: document.getElementById('settingsTimezone')?.value || 'Europe/Moscow',
            color_primary: document.getElementById('colorPrimary')?.value || '#6366f1',
            color_secondary: document.getElementById('colorSecondary')?.value || '#ec4899'
        };

        console.log('Settings data:', payload);

        const result = await this.api.fetch('/api/settings', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });

        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить изменения';
        }

        if (!result.success) {
            const errorMsg = result.data?.errors 
                ? Object.values(result.data.errors).flat().join(', ') 
                : result.error || 'Ошибка сохранения настроек';
            this.showNotification(`Ошибка: ${errorMsg}`, 'error');
            return;
        }

        console.log('✅ Настройки сохранены в БД');

        this.showNotification('✅ Настройки успешно сохранены', 'success');
    }

    changePassword() {
        const current = document.getElementById('currentPassword')?.value;
        const newPass = document.getElementById('newPassword')?.value;
        const confirm = document.getElementById('confirmPassword')?.value;

        if (!current || !newPass || !confirm) {
            this.showNotification('⚠️ Заполните все поля', 'warning');
            return;
        }

        if (current !== 'admin123') {
            this.showNotification('❌ Неверный текущий пароль', 'error');
            return;
        }

        if (newPass !== confirm) {
            this.showNotification('❌ Пароли не совпадают', 'error');
            return;
        }

        if (newPass.length < 6) {
            this.showNotification('⚠️ Пароль должен быть минимум 6 символов', 'warning');
            return;
        }

        this.showNotification('⚠️ В демо-версии смена пароля недоступна', 'warning');
    }

    exportAllData() {
        db.exportData();
        this.showNotification('✅ Все данные экспортированы', 'success');
    }

    importAllData() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';

        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                try {
                    const success = db.importData(event.target.result);
                    if (success) {
                        this.showNotification('✅ Данные импортированы успешно', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.showNotification('❌ Ошибка импорта данных', 'error');
                    }
                } catch (error) {
                    this.showNotification('❌ Неверный формат файла', 'error');
                }
            };
            reader.readAsText(file);
        });

        input.click();
    }

    resetDatabase() {
        if (!confirm('⚠️ ВЫ УВЕРЕНЫ? Все данные будут удалены и восстановлены начальные!')) return;
        if (!confirm('❗ Последнее предупреждение! Это действие необратимо!')) return;

        db.resetToDefault();
        this.showNotification('✅ База данных сброшена', 'success');
        setTimeout(() => location.reload(), 1500);
    }

    // ========================================
    // NOTIFICATIONS
    // ========================================

    loadNotifications() {
        this.notifications = [];

        const newOrders = (db.getData('orders') || []).filter(o => o.status === 'new');

        newOrders.forEach(order => {
            this.notifications.push({
                id: order.id,
                type: 'order',
                title: 'Новый заказ',
                text: `От ${order.clientName || order.name}`,
                time: order.createdAt,
                unread: true
            });
        });

        this.renderNotifications();
    }

    renderNotifications() {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--admin-text-secondary);">Нет новых уведомлений</div>';
            return;
        }

        container.innerHTML = this.notifications.map(notif => `
            <div class="notification-item ${notif.unread ? 'unread' : ''}" onclick="admin.handleNotificationClick('${notif.id}', '${notif.type}')">
                <div class="notification-title">${notif.title}</div>
                <div class="notification-text">${notif.text}</div>
                <div class="notification-time">${this.getRelativeTime(notif.time)}</div>
            </div>
        `).join('');
    }

    handleNotificationClick(id, type) {
        this.closeAllDropdowns();

        if (type === 'order') {
            this.navigateToPage('orders');
            setTimeout(() => {
                this.viewOrder(id);
            }, 300);
        }
    }

    markAllRead() {
        this.notifications.forEach(n => n.unread = false);
        this.renderNotifications();
        document.getElementById('notificationDot')?.style.setProperty('display', 'none');
    }

    // ========================================
    // QUICK ACTIONS
    // ========================================

    quickToggleTelegram(enabled) {
        CONFIG.features.telegramNotifications = enabled;

        db.updateSettings({
            telegramNotifications: enabled
        });

        this.showNotification(enabled ? '✅ Telegram уведомления включены' : '⚠️ Telegram уведомления выключены', 'info');
    }

    quickToggleTheme(isDark) {
        if (isDark) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('adminTheme', 'dark');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('adminTheme', 'light');
        }
    }

    clearCache() {
        if (!confirm('Очистить кеш браузера?')) return;

        localStorage.removeItem('3dprintpro_cache');
        this.showNotification('✅ Кеш очищен', 'success');
    }

    viewProfile() {
        const content = `
            <div class="detail-section">
                <h4>Информация профиля</h4>
                <p><strong>Имя:</strong> ${this.currentUser?.name || 'Администратор'}</p>
                <p><strong>Логин:</strong> ${this.currentUser?.login || 'admin'}</p>
                <p><strong>Роль:</strong> ${this.currentUser?.role || 'admin'}</p>
                <p><strong>Последний вход:</strong> ${this.formatDate(this.currentUser?.loginTime || new Date().toISOString())}</p>
            </div>
            <div class="detail-section">
                <h4>Статистика</h4>
                <p><strong>Всего заказов:</strong> ${(db.getData('orders') || []).length}</p>
                <p><strong>Работ в портфолио:</strong> ${(db.getData('portfolio') || []).length}</p>
                <p><strong>Отзывов:</strong> ${(db.getData('testimonials') || []).length}</p>
            </div>
        `;

        this.createModal('Профиль администратора', content);
    }

    // ========================================
    // MODAL SYSTEM
    // ========================================

    createModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'custom-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        `;

        modal.innerHTML = `
            <div class="admin-modal-content" style="max-width: 600px; width: 100%; background: white; border-radius: 15px; max-height: 90vh; overflow-y: auto; animation: modalSlideIn 0.3s ease;">
                <div class="modal-header" style="padding: 25px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: white; z-index: 10; border-radius: 15px 15px 0 0;">
                    <h3 style="font-size: 20px; margin: 0; color: var(--admin-text);">${title}</h3>
                    <button class="modal-close" style="width: 35px; height: 35px; border-radius: 8px; border: none; background: var(--admin-bg); font-size: 24px; cursor: pointer; transition: all 0.2s; color: var(--admin-text); display: flex; align-items: center; justify-content: center;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 25px; color: var(--admin-text);">
                    ${content}
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        if (document.body.classList.contains('dark-mode')) {
            modal.querySelector('.admin-modal-content').style.background = 'var(--admin-card)';
            modal.querySelector('.modal-header').style.background = 'var(--admin-card)';
        }

        const closeBtn = modal.querySelector('.modal-close');
        closeBtn.addEventListener('click', () => modal.remove());

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        closeBtn.addEventListener('mouseenter', () => {
            closeBtn.style.background = 'var(--admin-danger)';
            closeBtn.style.color = 'white';
        });
        closeBtn.addEventListener('mouseleave', () => {
            closeBtn.style.background = 'var(--admin-bg)';
            closeBtn.style.color = 'var(--admin-text)';
        });

        return modal;
    }

    showFormModal(title, fields, onSubmit) {
        const formId = 'dynamicForm_' + Date.now();

        const formHTML = `
            <form id="${formId}" class="dynamic-form">
                ${fields.map(field => field.render()).join('')}
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--admin-border);">
                    <button type="button" class="btn btn-outline cancel-btn">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        `;

        const modal = this.createModal(title, formHTML);
        const form = document.getElementById(formId);

        form.querySelector('.cancel-btn').addEventListener('click', () => {
            modal.remove();
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const data = {};
            const validator = new Validator();
            let isValid = true;

            fields.forEach(field => {
                let value = formData.get(field.name);

                if (field.type === 'checkbox') {
                    value = form.querySelector(`[name="${field.name}"]`)?.checked || false;
                } else if (field.type === 'number') {
                    value = value ? parseFloat(value) : 0;
                }

                data[field.name] = value;

                const validation = field.validate(value);
                if (!validation.isValid) {
                    isValid = false;
                    Object.assign(validator.errors, validation.errors);
                }
            });

            if (!isValid) {
                validator.showErrors(form);
                this.showNotification('Исправьте ошибки в форме', 'error');
                return;
            }

            onSubmit(data);
            modal.remove();
        });
    }

    closeAllModals() {
        document.querySelectorAll('.custom-modal').forEach(modal => {
            modal.remove();
        });
    }

    // ========================================
    // NOTIFICATIONS TOAST
    // ========================================

    showNotification(message, type = 'info') {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#6366f1'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const notification = document.createElement('div');
        notification.className = 'admin-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 18px 25px;
            background: ${colors[type]};
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 100000;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
            font-weight: 500;
        `;

        notification.innerHTML = `
            <i class="fas ${icons[type]}" style="font-size: 20px;"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    getStatusName(status) {
        const names = {
            'new': 'Новый',
            'processing': 'В работе',
            'completed': 'Выполнен',
            'cancelled': 'Отменён'
        };
        return names[status] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    getRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'только что';
        if (diffMins < 60) return `${diffMins} мин назад`;
        if (diffHours < 24) return `${diffHours} ч назад`;
        if (diffDays < 7) return `${diffDays} дн назад`;

        return this.formatDate(dateString);
    }
}

// ========================================
// GLOBAL INSTANCE & INITIALIZATION
// ========================================

const admin = new AdminPanel();

document.addEventListener('DOMContentLoaded', () => {
    admin.init();
});

function logout() {
    admin.logout();
}