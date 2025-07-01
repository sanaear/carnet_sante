# Carnet de Santé - Healthcare Management System

A comprehensive healthcare management system built with Symfony 7.2 that allows medical professionals and patients to manage medical records, appointments, and prescriptions.

## Features

- **User Authentication & Authorization**
  - Secure login system with different user roles (Admin, Doctor, Patient)
  - Registration system for new users
  - Password reset functionality

- **Patient Management**
  - Personal health records
  - Medical history tracking
  - Prescription management

- **Doctor Portal**
  - Patient record access
  - Prescription generation
  - Appointment scheduling

- **Administration**
  - User management
  - System configuration
  - Access control

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm/yarn (for frontend assets)
- MySQL/MariaDB database
- Symfony CLI (optional but recommended)

## Installation

1. **Clone the repository**
   ```bash
   git clone [repository-url].git
   cd carnet_sante-main
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Configure environment**
   - Copy `.env` to `.env.local`
   - Update database configuration in `.env.local`
   - Configure mailer settings if needed

5. **Set up the database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

6. **Build assets**
   ```bash
   npm run dev
   ```

7. **Start the development server**
   ```bash
   symfony server:start
   ```

## Development

### Running Tests
```bash
php bin/phpunit
```

### Database Migrations
To create a new migration:
```bash
php bin/console make:migration
```

To apply migrations:
```bash
php bin/console doctrine:migrations:migrate
```

### Frontend Development
To watch for changes and rebuild assets:
```bash
npm run watch
```

## Environment Variables

- `APP_ENV`: Application environment (dev, test, prod)
- `APP_SECRET`: Application secret key
- `DATABASE_URL`: Database connection string
- `MAILER_DSN`: Mailer DSN for email notifications
- `WKHTMLTOPDF_PATH`: Path to wkhtmltopdf binary (for PDF generation)

## Security

- All passwords are hashed using the latest security standards
- CSRF protection is enabled
- Rate limiting is implemented for authentication endpoints
- Sensitive data is properly escaped in templates

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is proprietary software.

## Support

For support, please contact the development team at [support-email@example.com].
