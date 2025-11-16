-- ========================================
-- 3D Print Pro - Initial Seed Data
-- ========================================
-- This script populates the database with baseline content
-- Run after executing the migration script
-- ========================================

USE ch167436_3dprint;

-- ========================================
-- 1. DEFAULT ADMIN USER
-- ========================================
-- Password: admin123 (bcrypt hash - change in production!)
-- To generate new hash: bcrypt('admin123', 10)
INSERT INTO users (login, password_hash, name, email, role, active) VALUES
('admin', '$2a$10$xU8zDmqHvZLdJPp5aL0nGOQ7YWz9v1sxXUBNgGvhFJlN.K3zXqZ8K', 'Администратор', 'admin@3dprintpro.ru', 'admin', TRUE);

-- ========================================
-- 2. DEFAULT SERVICES
-- ========================================
INSERT INTO services (name, slug, icon, description, price, active, featured, display_order) VALUES
('FDM печать', 'fdm', 'fa-cube', 'Печать методом послойного наплавления. Идеально для прототипов и функциональных деталей.', 'от 50₽/г', TRUE, FALSE, 1),
('SLA/SLS печать', 'sla', 'fa-gem', 'Высокоточная печать с невероятной детализацией для самых требовательных проектов.', 'от 200₽/г', TRUE, TRUE, 2),
('Post-обработка', 'post', 'fa-cogs', 'Шлифовка, покраска, сборка. Доводим изделия до идеального состояния.', 'от 300₽', TRUE, FALSE, 3),
('3D моделирование', 'modeling', 'fa-drafting-compass', 'Создание 3D моделей по вашим эскизам, чертежам или идеям.', 'от 500₽/час', TRUE, FALSE, 4),
('3D сканирование', 'scanning', 'fa-scanner', 'Создание точных цифровых копий физических объектов.', 'от 1000₽', TRUE, FALSE, 5),
('Мелкосерийное производство', 'production', 'fa-industry', 'Изготовление партий деталей от 10 до 10000 штук.', 'Индивидуально', TRUE, FALSE, 6);

-- ========================================
-- 3. SERVICE FEATURES
-- ========================================
-- FDM печать features
INSERT INTO service_features (service_id, feature_text, display_order) VALUES
(1, 'Быстрое изготовление', 1),
(1, 'Низкая стоимость', 2),
(1, 'Прочные детали', 3),
(1, 'Широкий выбор материалов', 4);

-- SLA/SLS печать features
INSERT INTO service_features (service_id, feature_text, display_order) VALUES
(2, 'Высокая точность', 1),
(2, 'Гладкая поверхность', 2),
(2, 'Сложная геометрия', 3),
(2, 'Идеально для ювелирки', 4);

-- Post-обработка features
INSERT INTO service_features (service_id, feature_text, display_order) VALUES
(3, 'Профессиональная покраска', 1),
(3, 'Химическая обработка', 2),
(3, 'Сборка узлов', 3),
(3, 'Гарантия качества', 4);

-- 3D моделирование features
INSERT INTO service_features (service_id, feature_text, display_order) VALUES
(4, 'Опытные дизайнеры', 1),
(4, 'Любая сложность', 2),
(4, 'Быстрые правки', 3),
(4, 'Оптимизация для печати', 4);

-- 3D сканирование features
INSERT INTO service_features (service_id, feature_text, display_order) VALUES
(5, 'Точность до 0.05мм', 1),
(5, 'Объекты любого размера', 2),
(5, 'Обработка моделей', 3),
(5, 'Быстрое выполнение', 4);

-- Мелкосерийное производство features
INSERT INTO service_features (service_id, feature_text, display_order) VALUES
(6, 'Скидки на объем', 1),
(6, 'Контроль качества', 2),
(6, 'Быстрые сроки', 3),
(6, 'Упаковка и доставка', 4);

-- ========================================
-- 4. DEFAULT TESTIMONIALS
-- ========================================
INSERT INTO testimonials (name, position, avatar_url, rating, text, approved, display_order) VALUES
('Алексей Иванов', 'Директор, Tech Solutions', 'https://i.pravatar.cc/150?img=1', 5, 'Отличное качество печати! Заказывали прототипы корпусов для нашего устройства. Все выполнено точно в срок, консультации на высшем уровне.', TRUE, 1),
('Мария Петрова', 'Дизайнер', 'https://i.pravatar.cc/150?img=2', 5, 'Работаю с этой компанией уже год. Печатают мои художественные проекты с невероятной детализацией. Рекомендую!', TRUE, 2),
('Дмитрий Сидоров', 'Инженер-конструктор', 'https://i.pravatar.cc/150?img=3', 5, 'Профессиональный подход к каждому заказу. Помогли с оптимизацией моделей, что сэкономило время и деньги.', TRUE, 3),
('Елена Смирнова', 'Владелец бизнеса', 'https://i.pravatar.cc/150?img=4', 5, 'Заказывала мелкую серию деталей - все изготовлено качественно, упаковано аккуратно. Очень довольна сотрудничеством!', TRUE, 4);

-- ========================================
-- 5. DEFAULT FAQ
-- ========================================
INSERT INTO faq (question, answer, active, display_order) VALUES
('Какие форматы файлов вы принимаете?', 'Мы работаем с форматами STL, OBJ, 3MF, STEP. Если у вас файл в другом формате, свяжитесь с нами - мы найдем решение.', TRUE, 1),
('Сколько времени занимает изготовление?', 'Стандартный срок - 3-5 рабочих дней. Для небольших деталей возможна печать за 1 день. Есть услуга срочного изготовления (24 часа).', TRUE, 2),
('Какая минимальная толщина стенок?', 'Для FDM печати минимальная толщина - 1мм, для SLA/SLS - 0.5мм. Рекомендуем консультироваться перед печатью тонкостенных деталей.', TRUE, 3),
('Можно ли заказать постобработку?', 'Да, мы предлагаем шлифовку, покраску, химическую обработку, сборку. Все услуги можно выбрать в калькуляторе.', TRUE, 4),
('Есть ли скидки на большие объемы?', 'Да! При заказе от 10 деталей скидка 10%, от 50 деталей - 15%, от 100 деталей - индивидуальные условия.', TRUE, 5),
('Как происходит оплата?', 'Принимаем оплату по безналичному расчету, банковским картам, электронным кошелькам. Для юр.лиц работаем по договору с отсрочкой.', TRUE, 6);

-- ========================================
-- 6. MATERIALS (Calculator Configuration)
-- ========================================
INSERT INTO materials (material_key, name, price, technology, active, display_order) VALUES
('pla', 'PLA', 50.00, 'fdm', TRUE, 1),
('abs', 'ABS', 60.00, 'fdm', TRUE, 2),
('petg', 'PETG', 70.00, 'fdm', TRUE, 3),
('nylon', 'Nylon', 120.00, 'fdm', TRUE, 4),
('tpu', 'TPU (Flex)', 150.00, 'fdm', TRUE, 5),
('standard_resin', 'Standard Resin', 200.00, 'sla', TRUE, 6),
('tough_resin', 'Tough Resin', 250.00, 'sla', TRUE, 7),
('flexible_resin', 'Flexible Resin', 280.00, 'sla', TRUE, 8),
('pa12', 'PA12 Nylon', 150.00, 'sls', TRUE, 9),
('tpu_sls', 'TPU SLS', 180.00, 'sls', TRUE, 10);

-- ========================================
-- 7. ADDITIONAL SERVICES (Calculator Configuration)
-- ========================================
INSERT INTO additional_services (service_key, name, price, unit, active, display_order) VALUES
('modeling', '3D моделирование', 500.00, 'час', TRUE, 1),
('postProcessing', 'Постобработка', 300.00, 'шт', TRUE, 2),
('painting', 'Покраска', 500.00, 'шт', TRUE, 3),
('express', 'Срочное изготовление', 1000.00, 'заказ', TRUE, 4);

-- ========================================
-- 8. QUALITY LEVELS (Calculator Configuration)
-- ========================================
INSERT INTO quality_levels (quality_key, name, price_multiplier, time_multiplier, active, display_order) VALUES
('draft', 'Черновое', 0.80, 0.70, TRUE, 1),
('normal', 'Нормальное', 1.00, 1.00, TRUE, 2),
('high', 'Высокое', 1.30, 1.40, TRUE, 3),
('ultra', 'Ультра', 1.60, 2.00, TRUE, 4);

-- ========================================
-- 9. VOLUME DISCOUNTS (Calculator Configuration)
-- ========================================
INSERT INTO volume_discounts (min_quantity, discount_percent, active) VALUES
(10, 10.00, TRUE),
(50, 15.00, TRUE),
(100, 20.00, TRUE);

-- ========================================
-- 10. FORM FIELDS (Contact Form Configuration)
-- ========================================
INSERT INTO form_fields (form_type, field_name, label, field_type, required, enabled, placeholder, display_order, options) VALUES
('contact', 'name', 'Ваше имя', 'text', TRUE, TRUE, 'Иван Петров', 1, NULL),
('contact', 'email', 'Email', 'email', TRUE, TRUE, 'example@mail.com', 2, NULL),
('contact', 'phone', 'Телефон', 'tel', TRUE, TRUE, '+7 (999) 123-45-67', 3, NULL),
('contact', 'telegram', 'Telegram', 'text', FALSE, TRUE, '@username', 4, NULL),
('contact', 'subject', 'Тема обращения', 'select', FALSE, TRUE, 'Выберите тему', 5, JSON_ARRAY('Расчет стоимости', 'Консультация', 'Партнерство', 'Другое')),
('contact', 'message', 'Ваше сообщение', 'textarea', TRUE, TRUE, 'Опишите ваш заказ...', 6, NULL);

-- ========================================
-- 11. SITE SETTINGS (Singleton)
-- ========================================
INSERT INTO site_settings (
    site_name, 
    site_description, 
    contact_email, 
    contact_phone, 
    address, 
    working_hours, 
    timezone,
    social_links,
    theme,
    color_primary,
    color_secondary,
    notifications
) VALUES (
    '3D Print Pro',
    'Профессиональная 3D печать любой сложности',
    'info@3dprintpro.ru',
    '+7 (999) 123-45-67',
    'г. Москва, ул. Примерная, д. 123',
    'Пн-Пт: 9:00 - 18:00\nСб-Вс: Выходной',
    'Europe/Moscow',
    JSON_OBJECT(
        'vk', '',
        'telegram', 'https://t.me/PrintPro_Omsk',
        'whatsapp', '',
        'youtube', ''
    ),
    'light',
    '#6366f1',
    '#ec4899',
    JSON_OBJECT(
        'newOrders', TRUE,
        'newReviews', TRUE,
        'newMessages', TRUE
    )
);

-- ========================================
-- 12. INTEGRATIONS (Telegram)
-- ========================================
INSERT INTO integrations (integration_name, enabled, config) VALUES
('telegram', TRUE, JSON_OBJECT(
    'botToken', '8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI',
    'chatId', '',
    'apiUrl', 'https://api.telegram.org/bot',
    'contactUrl', 'https://t.me/PrintPro_Omsk'
));

-- ========================================
-- 13. SITE CONTENT (Hero and About sections)
-- ========================================
INSERT INTO site_content (section_key, title, content) VALUES
('hero', 'идеи в реальность', JSON_OBJECT(
    'subtitle', 'Профессиональная 3D печать любой сложности. Быстро, качественно, доступно.',
    'features', JSON_ARRAY(
        'Печать от 1 часа',
        '15+ материалов',
        'Гарантия качества'
    )
)),
('about', 'Лидеры в области 3D печати', JSON_OBJECT(
    'description', 'Мы - команда профессионалов с более чем 12-летним опытом в области аддитивных технологий. Наша миссия - делать 3D печать доступной и качественной для каждого.',
    'features', JSON_ARRAY(
        JSON_OBJECT(
            'title', 'Современное оборудование',
            'description', 'Работаем на принтерах последнего поколения'
        ),
        JSON_OBJECT(
            'title', 'Опытная команда',
            'description', '15 специалистов с профильным образованием'
        ),
        JSON_OBJECT(
            'title', 'Гарантия качества',
            'description', 'Все изделия проходят контроль качества'
        )
    )
));

-- ========================================
-- 14. SITE STATS (Singleton)
-- ========================================
INSERT INTO site_stats (total_projects, happy_clients, years_experience, awards) VALUES
(1500, 850, 12, 25);

-- ========================================
-- SEED DATA COMPLETE
-- ========================================
-- All baseline content has been inserted
-- You can now start using the application
-- ========================================
