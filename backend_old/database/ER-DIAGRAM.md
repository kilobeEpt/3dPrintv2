# Entity Relationship Diagram

## Visual Schema Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            3D PRINT PRO DATABASE                             │
│                           ch167436_3dprint (MySQL 8.0+)                      │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────┐
│                           AUTHENTICATION & SECURITY                           │
└──────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│       USERS         │
├─────────────────────┤
│ PK id               │
│ UK login            │──────┐
│ UK email            │      │
│    password_hash    │      │
│    name             │      │ Creates/Modifies
│    role (ENUM)      │      │
│    active           │      │
│    last_login_at    │      │
│    created_at       │      │
│    updated_at       │      │
└─────────────────────┘      │
                             │
                             ▼
                    ┌──────────────────┐
                    │   AUDIT_LOGS     │
                    ├──────────────────┤
                    │ PK id            │
                    │ FK user_id       │
                    │    entity_type   │
                    │    entity_id     │
                    │    action (ENUM) │
                    │    field_name    │
                    │    old_value     │
                    │    new_value     │
                    │    ip_address    │
                    │    user_agent    │
                    │    created_at    │
                    └──────────────────┘

┌──────────────────────────────────────────────────────────────────────────────┐
│                              CORE BUSINESS ENTITIES                           │
└──────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐         ┌──────────────────────┐
│      SERVICES       │◄────────│  SERVICE_FEATURES    │
├─────────────────────┤   1:N   ├──────────────────────┤
│ PK id               │         │ PK id                │
│ UK slug             │         │ FK service_id        │
│    name             │         │    feature_text      │
│    icon             │         │    display_order     │
│    description      │         └──────────────────────┘
│    price            │
│    active           │
│    featured         │
│    display_order    │
│    created_at       │
│    updated_at       │
└─────────────────────┘


┌─────────────────────┐         ┌──────────────────────┐
│      PORTFOLIO      │         │    TESTIMONIALS      │
├─────────────────────┤         ├──────────────────────┤
│ PK id               │         │ PK id                │
│    title            │         │    name              │
│    category (ENUM)  │         │    position          │
│    description      │         │    avatar_url        │
│    image_url        │         │    rating (1-5)      │
│    details          │         │    text              │
│    created_at       │         │    approved          │
│    updated_at       │         │    display_order     │
└─────────────────────┘         │    created_at        │
                                │    updated_at        │
                                └──────────────────────┘

┌─────────────────────┐
│        FAQ          │
├─────────────────────┤
│ PK id               │
│    question         │
│    answer           │
│    active           │
│    display_order    │
│    created_at       │
│    updated_at       │
└─────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────┐
│                        ORDERS & CUSTOMER SUBMISSIONS                          │
└──────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│       ORDERS        │
├─────────────────────┤
│ PK id               │
│ UK order_number     │
│    type (ENUM)      │          'order' → Has calculator_data JSON
│    status (ENUM)    │          'contact' → General inquiry
│    client_name      │
│    client_email     │
│    client_phone     │
│    telegram         │
│    service          │
│    subject          │
│    message          │
│    amount           │
│    calculator_data  │──────► JSON: {
│    telegram_sent    │            technology, material, weight,
│    telegram_sent_at │            quantity, infill, quality,
│    created_at       │            costs, discounts, total, etc.
│    updated_at       │          }
└─────────────────────┘

Status Flow: new → processing → completed
               ↓
           cancelled

┌──────────────────────────────────────────────────────────────────────────────┐
│                         CALCULATOR CONFIGURATION                              │
└──────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐    ┌──────────────────────┐    ┌──────────────────────┐
│     MATERIALS       │    │ ADDITIONAL_SERVICES  │    │   QUALITY_LEVELS     │
├─────────────────────┤    ├──────────────────────┤    ├──────────────────────┤
│ PK id               │    │ PK id                │    │ PK id                │
│ UK material_key     │    │ UK service_key       │    │ UK quality_key       │
│    name             │    │    name              │    │    name              │
│    price (per g)    │    │    price             │    │    price_multiplier  │
│    technology       │    │    unit              │    │    time_multiplier   │
│    active           │    │    active            │    │    active            │
│    display_order    │    │    display_order     │    │    display_order     │
│    created_at       │    │    created_at        │    │    created_at        │
│    updated_at       │    │    updated_at        │    │    updated_at        │
└─────────────────────┘    └──────────────────────┘    └──────────────────────┘
  Examples:                  Examples:                   Examples:
  • pla: 50₽/g              • modeling: 500₽/час        • draft: 0.8x
  • abs: 60₽/g              • postProcessing: 300₽/шт   • normal: 1.0x
  • petg: 70₽/g             • painting: 500₽/шт         • high: 1.3x
  Technology:               • express: 1000₽/заказ      • ultra: 1.6x
  • fdm, sla, sls

┌──────────────────────┐
│  VOLUME_DISCOUNTS    │
├──────────────────────┤
│ PK id                │
│    min_quantity      │
│    discount_percent  │
│    active            │
│    created_at        │
│    updated_at        │
└──────────────────────┘
  Examples:
  • 10+ units: 10% off
  • 50+ units: 15% off
  • 100+ units: 20% off

┌──────────────────────────────────────────────────────────────────────────────┐
│                          SITE CONFIGURATION (Singletons)                      │
└──────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐    ┌──────────────────────┐    ┌──────────────────────┐
│   SITE_SETTINGS     │    │    SITE_CONTENT      │    │    SITE_STATS        │
├─────────────────────┤    ├──────────────────────┤    ├──────────────────────┤
│ PK id (singleton)   │    │ PK id                │    │ PK id (singleton)    │
│    site_name        │    │ UK section_key       │    │    total_projects    │
│    site_description │    │    title             │    │    happy_clients     │
│    contact_email    │    │    content (JSON)    │    │    years_experience  │
│    contact_phone    │    │    created_at        │    │    awards            │
│    address          │    │    updated_at        │    │    updated_at        │
│    working_hours    │    └──────────────────────┘    └──────────────────────┘
│    timezone         │      Sections:                  Manually updated
│    social_links     │──►  • hero                     statistics shown
│    theme            │      • about                    on homepage
│    color_primary    │
│    color_secondary  │
│    notifications    │──► JSON: { newOrders, newReviews, newMessages }
│    created_at       │
│    updated_at       │
└─────────────────────┘

social_links JSON: { vk, telegram, whatsapp, youtube }

┌──────────────────────────────────────────────────────────────────────────────┐
│                      DYNAMIC FORMS & INTEGRATIONS                             │
└──────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐    ┌──────────────────────┐
│    FORM_FIELDS      │    │    INTEGRATIONS      │
├─────────────────────┤    ├──────────────────────┤
│ PK id               │    │ PK id                │
│ UK (form_type +     │    │ UK integration_name  │
│     field_name)     │    │    enabled           │
│    label            │    │    config (JSON)     │
│    field_type       │    │    created_at        │
│    required         │    │    updated_at        │
│    enabled          │    └──────────────────────┘
│    placeholder      │      Examples:
│    display_order    │      • telegram
│    options (JSON)   │──►    config: {
│    created_at       │          botToken,
│    updated_at       │          chatId,
└─────────────────────┘          apiUrl,
  Form Types:                    contactUrl
  • contact                    }
  • order                    • email (future)
                            • sms (future)
  Field Types:
  • text, email, tel
  • textarea, select
  • checkbox, file

┌──────────────────────────────────────────────────────────────────────────────┐
│                              KEY RELATIONSHIPS                                │
└──────────────────────────────────────────────────────────────────────────────┘

FOREIGN KEYS:
  service_features.service_id ──► services.id (CASCADE DELETE)
  audit_logs.user_id ──────────► users.id (SET NULL)

SOFT REFERENCES (No FK):
  orders.service ──(soft)──► services.name

SINGLETON TABLES (Should have 1 row):
  • site_settings (id=1)
  • site_stats (id=1)

MULTI-ROW SINGLETONS (Section-based):
  • site_content (section_key: 'hero', 'about', etc.)
  • integrations (integration_name: 'telegram', 'email', etc.)

┌──────────────────────────────────────────────────────────────────────────────┐
│                                  INDEXES                                      │
└──────────────────────────────────────────────────────────────────────────────┘

UNIQUE INDEXES:
  • users: login, email
  • services: slug
  • orders: order_number
  • materials: material_key
  • additional_services: service_key
  • quality_levels: quality_key
  • form_fields: (form_type, field_name)
  • integrations: integration_name
  • site_content: section_key

PERFORMANCE INDEXES:
  • orders: status, type, client_email, created_at, (status+created_at)
  • services: active, featured, display_order
  • testimonials: approved, rating, display_order
  • faq: active, display_order
  • materials: technology, active
  • All display_order fields

FULL-TEXT SEARCH:
  • orders: (client_name, client_email, message)

┌──────────────────────────────────────────────────────────────────────────────┐
│                            ENUM DEFINITIONS                                   │
└──────────────────────────────────────────────────────────────────────────────┘

users.role:
  • admin, manager, user

orders.type:
  • order, contact

orders.status:
  • new, processing, completed, cancelled

portfolio.category:
  • prototype, functional, art, industrial

materials.technology:
  • fdm, sla, sls

form_fields.form_type:
  • contact, order

form_fields.field_type:
  • text, email, tel, textarea, select, checkbox, file, number, url, date

audit_logs.action:
  • create, update, delete

┌──────────────────────────────────────────────────────────────────────────────┐
│                          DATA FLOW OVERVIEW                                   │
└──────────────────────────────────────────────────────────────────────────────┘

PUBLIC SITE:
  1. Load services (active only) + features
  2. Load portfolio items (filter by category)
  3. Load testimonials (approved only)
  4. Load FAQ (active only)
  5. Load site_content (hero, about)
  6. Load site_stats
  7. Load calculator config (materials, services, quality, discounts)
  8. Load form_fields (contact form)

ORDER SUBMISSION:
  1. Client fills form (validated by form_fields)
  2. Calculator computes price (uses materials, quality, discounts)
  3. Create order record (type='order', status='new')
  4. Store calculator_data as JSON
  5. Send Telegram notification (via integrations.telegram)
  6. Mark telegram_sent=TRUE

ADMIN PANEL:
  1. Login via users table (verify password_hash)
  2. Dashboard: Aggregate orders by status
  3. CRUD operations on all entities
  4. Update site_settings, site_content
  5. Configure calculator pricing
  6. Approve testimonials
  7. Audit trail logged to audit_logs

┌──────────────────────────────────────────────────────────────────────────────┐
│                              NOTES                                            │
└──────────────────────────────────────────────────────────────────────────────┘

• All tables use utf8mb4 charset for emoji support
• Timestamps: created_at and updated_at on all main tables
• Soft deletes via 'active' flags (services, faq, materials, etc.)
• JSON columns used for complex nested data (calculator_data, config fields)
• display_order allows manual sorting (lower number = higher priority)
• CHECK constraint: testimonials.rating BETWEEN 1 AND 5
• Order numbers: Format 'ORD-{timestamp}' (generated server-side)
• Password hashing: bcrypt with cost factor 10
• Audit logs are optional but recommended for compliance

```

## Database Statistics (After Seed)

| Table                 | Rows | Notes                          |
|-----------------------|------|--------------------------------|
| users                 | 1    | Default admin user             |
| services              | 6    | All service offerings          |
| service_features      | 24   | 4 features × 6 services        |
| portfolio             | 0    | Add your projects              |
| testimonials          | 4    | Sample reviews (approved)      |
| faq                   | 6    | Common questions               |
| orders                | 0    | Customer orders go here        |
| materials             | 10   | FDM, SLA, SLS materials        |
| additional_services   | 4    | Modeling, painting, etc.       |
| quality_levels        | 4    | Draft to ultra                 |
| volume_discounts      | 3    | 10+, 50+, 100+ quantity tiers  |
| form_fields           | 6    | Contact form configuration     |
| site_settings         | 1    | Singleton                      |
| site_content          | 2    | hero, about sections           |
| site_stats            | 1    | Singleton                      |
| integrations          | 1    | Telegram configuration         |
| audit_logs            | 0    | Populated as changes occur     |

**Total**: 17 tables, ~63 initial records

## Schema Files

- **Migration**: `/backend/database/migrations/20231113_initial.sql` (371 lines)
- **Seed Data**: `/backend/database/seeds/initial_data.sql` (238 lines)
- **Documentation**: `/docs/db-schema.md` (comprehensive guide)

---

*Last Updated: 2023-11-13*
