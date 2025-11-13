# 3D Print Pro

Professional 3D printing service website with integrated order management, pricing calculator, and admin panel.

## Overview

3D Print Pro is a complete solution for managing a 3D printing service business. It includes:

- **Public Website** - Marketing site with services showcase, portfolio, testimonials, FAQ, and contact form
- **Price Calculator** - Interactive calculator for instant pricing based on material, quality, quantity, and services
- **Order Management** - Track customer orders and contact submissions
- **Admin Panel** - Full CRUD interface for managing content, orders, pricing, and settings
- **Telegram Integration** - Instant notifications for new orders
- **MySQL Database** - Robust backend data storage with comprehensive schema

## Tech Stack

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Custom styles with animations and gradients
- **Vanilla JavaScript** - Modular ES6+ code
- **Chart.js** - Analytics and reporting charts
- **Font Awesome** - Icon library

### Backend (Database)
- **MySQL 8.0+** - Relational database
- **utf8mb4** - Full Unicode support including emojis
- **JSON Columns** - For flexible nested data structures

### Integrations
- **Telegram Bot API** - Order notifications
- **Browser localStorage** - Client-side data persistence (legacy)

## Project Structure

```
3dprint-pro/
├── index.html              # Public marketing site
├── admin.html              # Admin panel SPA
├── config.js               # Global configuration
├── css/
│   ├── styles.css          # Main styles
│   └── admin.css           # Admin panel styles
├── js/
│   ├── main.js             # Public site logic
│   ├── admin.js            # Admin panel logic
│   ├── calculator.js       # Price calculation engine
│   ├── database.js         # LocalStorage wrapper (legacy)
│   ├── validators.js       # Form validation
│   └── telegram.js         # Telegram integration
├── backend/
│   └── database/
│       ├── migrations/
│       │   └── 20231113_initial.sql       # Schema creation
│       ├── seeds/
│       │   └── initial_data.sql           # Baseline data
│       ├── README.md                      # Quick setup guide
│       ├── ER-DIAGRAM.md                  # Visual schema
│       └── VALIDATION_CHECKLIST.md        # Setup verification
└── docs/
    ├── data-model.md       # Domain model specification
    └── db-schema.md        # Database documentation
```

## Features

### Public Website
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Interactive service cards with modals
- ✅ Portfolio gallery with category filtering
- ✅ Customer testimonials carousel
- ✅ FAQ accordion
- ✅ Real-time price calculator
- ✅ Contact form with validation
- ✅ Telegram notifications
- ✅ Smooth animations and transitions

### Price Calculator
- ✅ Support for FDM, SLA, SLS technologies
- ✅ 10+ material options with different pricing
- ✅ Quality level selection (draft to ultra)
- ✅ Quantity-based volume discounts
- ✅ Additional services (modeling, painting, post-processing)
- ✅ Real-time cost breakdown
- ✅ Estimated completion time
- ✅ Direct order submission

### Admin Panel
- ✅ Secure login (username/password)
- ✅ Dashboard with key metrics and charts
- ✅ Order management (view, update status, filter, search)
- ✅ Service management (CRUD operations)
- ✅ Portfolio management with image uploads
- ✅ Testimonials approval workflow
- ✅ FAQ management
- ✅ Calculator configuration (materials, services, quality levels, discounts)
- ✅ Form field customization
- ✅ Site settings editor
- ✅ Content editor (hero, about sections)
- ✅ Telegram integration setup
- ✅ Data import/export (JSON)
- ✅ Audit trail

## Getting Started

### Prerequisites

- Web browser (Chrome, Firefox, Safari, Edge)
- Web server (for production deployment)
- MySQL 8.0+ (for backend database)
- Text editor or IDE

### Installation

#### 1. Clone Repository

```bash
git clone https://github.com/yourusername/3dprint-pro.git
cd 3dprint-pro
```

#### 2. Set Up Database

**Create MySQL database and run migrations:**

```bash
# Create database and schema
mysql -u root -p < backend/database/migrations/20231113_initial.sql

# Load initial data
mysql -u root -p ch167436_3dprint < backend/database/seeds/initial_data.sql
```

**Verify installation:**

```bash
mysql -u root -p ch167436_3dprint -e "SHOW TABLES;"
```

See [Database Setup Guide](backend/database/README.md) for detailed instructions.

#### 3. Configure Settings

**Update `config.js`:**

```javascript
const CONFIG = {
    siteName: 'Your 3D Print Business',
    siteUrl: 'https://yourdomain.com',
    telegram: {
        botToken: 'YOUR_BOT_TOKEN',
        chatId: 'YOUR_CHAT_ID',
    },
    // ... other settings
};
```

**Configure Telegram Bot:**

1. Create bot with [@BotFather](https://t.me/botfather)
2. Get bot token
3. Send message to your bot
4. Get chat ID from admin panel
5. Update integration settings

#### 4. Deploy

**Option A: Static Hosting (Netlify, Vercel, GitHub Pages)**

```bash
# Build not required - pure static files
# Just deploy the entire directory
```

**Option B: Traditional Web Server (Apache, Nginx)**

```bash
# Copy files to web root
sudo cp -r * /var/www/html/

# Set permissions
sudo chown -R www-data:www-data /var/www/html/
```

**Option C: Local Development**

```bash
# Use Python's built-in server
python3 -m http.server 8000

# Or Node.js http-server
npx http-server -p 8000
```

Visit: `http://localhost:8000`

### First Login

**Admin Panel Credentials:**
- URL: `http://yourdomain.com/admin.html`
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Change the default password immediately!

## Configuration

### Database Configuration

The application currently uses localStorage for client-side persistence. To migrate to MySQL:

1. **Set up backend API** (Node.js/Express, PHP, Python, etc.)
2. **Create database user:**
   ```sql
   CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'app_user'@'localhost';
   ```
3. **Update application** to use API endpoints instead of localStorage
4. **Run migration script** to convert localStorage data to MySQL

See [Database Schema Documentation](docs/db-schema.md) for detailed schema information.

### Calculator Pricing

Update pricing in Admin Panel → Calculator Settings:

- **Materials**: Price per gram for each material
- **Services**: Additional services (modeling, painting, etc.)
- **Quality**: Multipliers for print quality (draft to ultra)
- **Discounts**: Volume-based discount tiers

### Form Customization

Customize contact form fields in Admin Panel → Form Fields:

- Add/remove fields
- Change field types
- Set required/optional
- Reorder fields
- Configure select options

### Telegram Setup

1. Create bot with [@BotFather](https://t.me/botfather)
2. Get bot token: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`
3. Start chat with your bot
4. In Admin Panel → Telegram Settings:
   - Enter bot token
   - Click "Get Chat ID"
   - Save settings
5. Test notification

## Database Schema

The MySQL schema includes 17 tables:

### Core Entities
- **users** - Admin authentication
- **orders** - Customer orders and submissions
- **services** - Service catalog with features
- **portfolio** - Project showcase
- **testimonials** - Customer reviews
- **faq** - Frequently asked questions

### Calculator Configuration
- **materials** - 3D printing materials and pricing
- **additional_services** - Extra services
- **quality_levels** - Quality settings
- **volume_discounts** - Discount tiers

### Site Configuration
- **site_settings** - Global settings (singleton)
- **site_content** - Editable content sections
- **site_stats** - Site statistics (singleton)
- **integrations** - External service configs
- **form_fields** - Dynamic form configuration
- **audit_logs** - Change tracking

See [Database Documentation](docs/db-schema.md) for complete schema details and ER diagrams.

## Usage

### Public Site

1. Browse services and portfolio
2. Use price calculator to estimate costs
3. Submit order or contact inquiry
4. Receive confirmation

### Admin Panel

1. Login at `/admin.html`
2. View dashboard with metrics
3. Manage orders (update status, add notes)
4. Update content (services, portfolio, testimonials)
5. Configure pricing and settings
6. Export data for backup

## API Documentation

*Coming soon - Backend API is planned for future development*

The application currently operates as a static site with client-side data storage. A REST API will be added to integrate with the MySQL database.

Planned endpoints:
- `GET /api/services` - List services
- `POST /api/orders` - Create order
- `GET /api/orders` - List orders (admin)
- `PUT /api/orders/:id` - Update order (admin)
- ... (see [Database Documentation](docs/db-schema.md) for full API spec)

## Database Backup

### Automated Backups

**Set up daily cron job:**

```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * mysqldump -u backup_user -p'password' ch167436_3dprint > /backups/3dprint_$(date +\%Y\%m\%d).sql
```

### Manual Backup

```bash
mysqldump -u root -p ch167436_3dprint > backup_$(date +%Y%m%d).sql
```

### Restore from Backup

```bash
mysql -u root -p ch167436_3dprint < backup_20231113.sql
```

## Security

### Production Checklist

- [ ] Change default admin password
- [ ] Use HTTPS (SSL certificate)
- [ ] Secure Telegram bot token (environment variables)
- [ ] Implement rate limiting on forms
- [ ] Enable CORS properly
- [ ] Sanitize all inputs
- [ ] Use prepared statements (when adding backend)
- [ ] Regular security updates
- [ ] Set up database backups
- [ ] Restrict database user privileges
- [ ] Enable MySQL SSL connections

### Password Security

Admin passwords are hashed using bcrypt (cost factor 10). To change password:

```sql
-- Generate new hash (use bcrypt library)
UPDATE users 
SET password_hash = '$2a$10$NEW_HASH_HERE' 
WHERE login = 'admin';
```

## Troubleshooting

### Database Issues

**Connection errors:**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u root -p -e "SELECT 1;"
```

**Character encoding errors:**
```sql
-- Verify charset
SHOW VARIABLES LIKE 'character_set%';

-- Should be utf8mb4
```

See [Validation Checklist](backend/database/VALIDATION_CHECKLIST.md) for comprehensive troubleshooting.

### Calculator Not Working

1. Check browser console for errors
2. Verify CONFIG loaded properly
3. Check material prices are set
4. Test in different browser

### Telegram Notifications Failing

1. Verify bot token is correct
2. Check chat ID is set
3. Ensure bot is not blocked
4. Test with "Send Test Message" in admin panel
5. Check browser console for API errors

### Admin Panel Login Issues

1. Verify credentials (admin/admin123 by default)
2. Check browser localStorage (credentials stored there temporarily)
3. Clear browser cache and cookies
4. Try incognito/private mode

## Development

### Adding New Features

1. Update data model in `docs/data-model.md`
2. Create migration script if database changes needed
3. Update frontend code
4. Test thoroughly
5. Update documentation

### Code Style

- Use ES6+ features
- Modular code organization
- Clear variable/function names
- Comments for complex logic
- Consistent indentation (2 or 4 spaces)

### Testing

Manual testing checklist:
- [ ] All forms submit correctly
- [ ] Calculator produces accurate results
- [ ] Admin CRUD operations work
- [ ] Telegram notifications send
- [ ] Mobile responsive
- [ ] Cross-browser compatible

## Performance

### Optimization Tips

- Enable gzip compression on server
- Minify CSS/JS for production
- Optimize images (compress, use WebP)
- Use CDN for static assets
- Cache static files
- Lazy load images
- Index database queries properly

### Database Performance

- Regularly run `OPTIMIZE TABLE`
- Monitor slow query log
- Add indexes for common queries
- Use connection pooling (when adding backend)
- Cache frequently accessed data

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## License

This project is licensed under the MIT License - see LICENSE file for details.

## Support

For questions or issues:

1. Check documentation in `/docs` folder
2. Review troubleshooting section
3. Open GitHub issue
4. Contact: info@3dprintpro.ru

## Roadmap

### Version 1.0 (Current)
- ✅ Static website with localStorage
- ✅ MySQL schema design
- ✅ Admin panel
- ✅ Telegram integration
- ✅ Price calculator

### Version 2.0 (Planned)
- [ ] Backend API (Node.js/Express)
- [ ] Database integration
- [ ] User authentication (JWT)
- [ ] Email notifications
- [ ] File upload for 3D models
- [ ] Payment gateway integration
- [ ] Customer accounts
- [ ] Order tracking
- [ ] Invoice generation

### Version 3.0 (Future)
- [ ] Multi-language support
- [ ] Mobile app (React Native)
- [ ] Advanced analytics
- [ ] Inventory management
- [ ] CRM features
- [ ] Automated pricing optimization
- [ ] 3D model viewer
- [ ] Print time estimation from STL files

## Changelog

### v1.0.0 (2023-11-13)
- Initial release
- Static website with full features
- MySQL database schema
- Admin panel
- Price calculator
- Telegram integration
- Complete documentation

## Acknowledgments

- Font Awesome for icons
- Chart.js for analytics charts
- Telegram Bot API
- MySQL for robust data storage
- Open source community

## Contact

**3D Print Pro**
- Website: https://3dprintpro.ru
- Email: info@3dprintpro.ru
- Telegram: [@PrintPro_Omsk](https://t.me/PrintPro_Omsk)

---

**Made with ❤️ for the 3D printing community**
