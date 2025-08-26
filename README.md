# SIBSE - Sistem Informasi Bank Sampah Enviro

Sistem Informasi Bank Sampah Enviro adalah aplikasi web untuk mengelola bank sampah dengan fitur lengkap.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/PostgreSQL/SQLite

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Configure your database in `.env` file

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. Build assets:
   ```bash
   npm run build
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

## Flux UI Components

This project uses [Livewire Flux](https://flux.livewire.io/) for UI components. Flux is a premium package that requires authentication.

### For Development (with Flux access)

If you have access to Flux:

1. Configure Composer authentication:
   ```bash
   composer config http-basic.composer.fluxui.dev your-username your-license-key
   ```

2. Install dependencies normally:
   ```bash
   composer install
   ```

### For Development (without Flux access)

If you don't have Flux credentials, the application will still work but some UI components may not render correctly. The GitHub workflows are configured to skip Flux installation when credentials are not available.

### For GitHub Actions

The GitHub workflows are configured to:
- Check if Flux credentials are available in repository secrets
- Only attempt Flux authentication if credentials exist
- Skip Flux package installation gracefully if credentials are missing

## GitHub Secrets

To enable Flux in GitHub Actions, add these secrets to your repository:

- `FLUX_USERNAME`: Your Flux username
- `FLUX_LICENSE_KEY`: Your Flux license key

## Features

- Authentication system
- Waste management
- Transaction tracking
- Financial reporting
- Admin dashboard with Filament
- Responsive UI with Flux components

## Technologies Used

- Laravel 12
- Livewire 3
- Filament 3
- Flux UI Components
- Tailwind CSS
- Alpine.js

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is proprietary software.
