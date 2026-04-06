# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 10 API with a Vue.js 3 frontend that serves as a home dashboard, aggregating data from multiple sources (SolarEdge, AnyList, National Weather Service) into a unified interface for tracking solar energy production, dinner planning, and weather information.

## Common Commands

### Backend (Laravel)
- `php artisan serve` - Start development server
- `php artisan migrate` - Run database migrations
- `php artisan tinker` - Open interactive shell
- `php artisan test` - Run all tests
- `php artisan test --filter TestName` - Run specific test
- `composer install` - Install PHP dependencies
- `./vendor/bin/pint` - Run Laravel Pint code formatter

### Frontend (Vue.js)
- `npm run dev` - Start Vite development server with HMR (Hot Module Replacement)
- `npm run build` - Production build (outputs to public/build)
- `npm install` - Install Node.js dependencies

### Custom Artisan Commands
- `php artisan dinner:protein-check` - Check and update protein assignments for dinners
- `php artisan user:create-admin` - Create admin user for Filament panel
- `php artisan user:check` - Check user configuration

## Architecture

### Service Layer Pattern

The application uses a service layer to encapsulate external API integrations and business logic:

- **Services** (`app/Services/`) - External API integrations and complex business logic
  - `AnyList.php` - Fetches dinner planning data from AnyList via iCal
  - `SolarEdge.php` - Retrieves solar production data from SolarEdge API
  - `Weather.php` - Gets weather forecasts from National Weather Service API
  - `DinnerService.php` - Business logic for dinner statistics, recommendations, and frequency analysis

Services are instantiated in the main Controller constructor and cached throughout the request lifecycle.

### Single Controller Pattern

Unlike typical Laravel applications, this uses a single controller (`app/Http/Controllers/Controller.php`) that handles all API endpoints. This controller:
- Initializes all services in the constructor
- Handles data aggregation from multiple sources in the `home()` method
- Manages dinner completion tracking and statistics
- Provides a `/gather/{platform}` endpoint for receiving webhook data from external platforms

### Data Caching Strategy

The application implements intelligent caching to minimize external API calls:
- Solar production data is stored in `SolarProductionDay` model
- Weather data is cached in `DailyWeather` model
- Dinner list data is cached when `forceRefresh` is not set
- Use `?force_refresh=1` query parameter to bypass cache and fetch fresh data

### Filament Admin Panel

The project uses Filament 3 (`filament/filament`) for admin functionality:
- Admin panel is accessible at `/admin` route
- Resources in `app/Filament/Resources/`:
  - `DinnerResource.php` - Manage dinner entries
  - `ProteinResource.php` - Manage protein types
- Authentication required (use `php artisan user:create-admin` to create admin user)

### Frontend Integration

The Vue.js 3 frontend is integrated into Laravel using Vite:
- Frontend source is in `resources/js/` directory
  - `resources/js/App.vue` - Main Vue application component
  - `resources/js/components/` - Vue components (HomeDisplay, DinnerItem, WeatherDay, SolarDaily, DateTime, StatsModal)
  - `resources/js/app.js` - Vue application bootstrap
- Build output goes to `public/build/` directory
- Main route (`routes/web.php`) serves the SPA for all non-API/admin routes using `@vite` directive
- API calls are made to `/api/*` endpoints
- Uses Chart.js for data visualization
- HMR (Hot Module Replacement) enabled during development
- Uses `@` alias to reference `resources/js/` directory in imports

### Database Models

Key models and their relationships:
- `Dinner` - Meal planning entries with optional protein relationship
- `Protein` - Protein types with vegetarian flag and color coding
- `SolarProductionDay` - Daily solar energy production cache
- `DailyWeather` - Weather forecast cache
- `GatheredData` - Generic webhook data collection
- `User` - Admin users for Filament panel

### API Authentication

The `/gather/{platform}` endpoint uses basic authentication via `api.basic` middleware configured in `config/api-auth.php`. Other endpoints are public (no authentication required).

### Configuration

Dashboard-specific configuration is in `config/dashboard.php`:
- `ANYLIST_CODE` - iCal key for AnyList integration
- `SOLAR_EDGE_SITE_ID` and `SOLAR_EDGE_API_KEY` - SolarEdge API credentials
- `WEATHER_LOCATION` - Latitude,longitude for weather data

## Key Implementation Details

### Dinner Recommendations Algorithm

`DinnerService::recommendations()` uses a complex SQL query that:
1. Weights dinners not made in 20+ days (weight: 1)
2. Weights historical dinners from this time of year (weight: 2)
3. Excludes recently made dinners (within 20 days)

### Solar Energy Tracking

- Solar production is fetched from SolarEdge API in watt-hours
- Data is aggregated by day and month
- Frontend displays daily production with comparison to max daily generation
- Monthly statistics are calculated from `SolarProductionDay` records

### Time Zone Handling

The application defaults to 'America/Denver' timezone. When working with dates:
- `startOfWeek()` calculations use Denver timezone
- API responses include ISO 8601 formatted dates (`format('c')`)
- Frontend handles timezone display

## Development Notes

- Node version is pinned to 16.19.0 via Volta (see `.nvmrc` and package.json)
- PostgreSQL is the primary database (based on SQL syntax in `DinnerService`)
- Vite builds assets to `public/build/` directory (managed by Laravel Vite plugin)
- The `/admin` route is reserved for Filament panel
- Vue components use Composition API with `<script setup>` syntax
