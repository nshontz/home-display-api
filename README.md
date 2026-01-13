# Home API

A Laravel-based API that aggregates and serves home dashboard data including dinner planning, solar energy production, and weather information.

## Features

- **Dinner Planning**: Integration with AnyList for meal planning and protein tracking
- **Solar Energy Monitoring**: Real-time solar production data via SolarEdge API
- **Weather Integration**: Current conditions and forecasts
- **Data Aggregation**: Centralized endpoint for home dashboard display
- **Vue.js Frontend**: Interactive frontend for data visualization

## Technology Stack

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Frontend**: Vue.js 3 with Chart.js for data visualization
- **Database**: PostgreSQL/MySQL compatible
- **APIs**: SolarEdge, National Weather Service, AnyList

## Project Structure

```
app/
├── Http/Controllers/    # API controllers
├── Models/             # Eloquent models for data entities
├── Services/           # External API service integrations
└── Console/Commands/   # Artisan commands

frontend/               # Vue.js frontend application
routes/api.php         # API route definitions
database/migrations/   # Database schema migrations
```

## Installation

1. Clone the repository
2. Install PHP dependencies: `composer install`
3. Install Node.js dependencies: `npm install`
4. Copy `.env.example` to `.env` and configure environment variables
5. Generate application key: `php artisan key:generate`
6. Run database migrations: `php artisan migrate`

## Configuration

Set the following environment variables in your `.env` file:

```env
# SolarEdge Configuration
SOLAREDGE_SITE_ID=your_site_id
SOLAREDGE_API_KEY=your_api_key

# AnyList Integration
ANYLIST_ICAL_KEY=your_ical_key

# Location for weather data
LOCATION=latitude,longitude

# API Authentication
API_BASIC_AUTH_USER=username
API_BASIC_AUTH_PASSWORD=password
```

## API Endpoints

- `GET /api/home` - Main dashboard data aggregation
- `GET /api/dinner/stats` - Dinner and protein statistics
- `POST /api/dinner/{uid}` - Update dinner completion status
- `POST /api/gather/{platform}` - Data collection endpoint (authenticated)

## Development

### Backend
```bash
php artisan serve
```

### Frontend
```bash
cd frontend
npm run serve
```

### Building for Production
```bash
cd frontend
npm run build
```

## License

MIT License