# Exchange Mini Engine

A limit-order exchange engine with real-time order matching, atomic execution, and instant WebSocket notifications.

## Features

- **Limit Order Trading**: Place buy/sell orders at specified prices
- **Real-time Order Matching**: Orders matched instantly with full-match-only rule
- **Atomic Execution**: Race-safe balance updates with database locks
- **1.5% Commission**: Transparent fee structure on matched trades
- **WebSocket Notifications**: Real-time updates via Pusher
- **RESTful API**: Clean API endpoints with Laravel Data DTOs
- **Service/Repository Pattern**: Clean architecture with interfaces

## Tech Stack

- **Backend**: Laravel 12, PHP 8.3
- **Frontend**: Vue.js 3 (Composition API), Tailwind CSS v4
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Real-time**: Pusher (WebSocket)
- **Containerization**: Docker & Docker Compose

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Node.js 18+ (for frontend development)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd exchange-app
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Configure Pusher credentials** in `.env`:
   ```env
   PUSHER_APP_ID=your_app_id
   PUSHER_APP_KEY=your_app_key
   PUSHER_APP_SECRET=your_app_secret
   PUSHER_APP_CLUSTER=your_cluster
   ```

4. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

5. **Install dependencies & run migrations**
   ```bash
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   ```

6. **Build frontend** (optional, for development)
   ```bash
   cd frontend
   npm install
   npm run build
   cp -r dist/assets ../public/
   cp dist/index.html ../resources/views/app.blade.php
   ```

7. **Access the application**
   - Web: http://localhost:8000
   - API: http://localhost:8000/api

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/register` | Register new user |
| POST | `/login` | Login user |
| POST | `/logout` | Logout user |
| POST | `/forgot-password` | Request password reset |
| POST | `/reset-password` | Reset password |

### Profile

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/profile` | Get user profile with balance and assets |

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List user's orders (with filters) |
| POST | `/api/orders` | Place a new order |
| POST | `/api/orders/{id}/cancel` | Cancel an open order |
| GET | `/api/my-orders` | Get all user's orders |
| GET | `/api/public/orders` | Get public order book |

### Order Request Body

```json
{
  "symbol": "BTC",
  "side": "buy",
  "price": 50000.00,
  "amount": 0.5
}
```

## Database Schema

### users

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key, auto-increment |
| name | string | User's name |
| email | string | Unique |
| email_verified_at | timestamp | Nullable |
| password | string | Hashed |
| balance | decimal(20,8) | USD balance, default 0 |
| remember_token | string | For "remember me" sessions |
| created_at | timestamp | |
| updated_at | timestamp | |

### assets

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| user_id | bigint | FK → users.id (cascade delete) |
| symbol | string | e.g., "BTC", "ETH" |
| amount | decimal(20,8) | Available balance |
| locked_amount | decimal(20,8) | Reserved for open orders |
| created_at | timestamp | |
| updated_at | timestamp | |

**Unique constraint:** `(user_id, symbol)`

### orders

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| user_id | bigint | FK → users.id (cascade delete) |
| symbol | string | e.g., "BTC" |
| side | enum | `'buy'` or `'sell'` |
| price | decimal(20,8) | Limit price in USD |
| amount | decimal(20,8) | Original order amount |
| remaining_amount | decimal(20,8) | Amount left to fill |
| status | tinyint | 1=Open, 2=Filled, 3=Cancelled |
| created_at | timestamp | |
| updated_at | timestamp | |

**Index:** `(symbol, status, price)` for efficient order matching

### trades

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| buyer_id | bigint | FK → users.id |
| seller_id | bigint | FK → users.id |
| symbol | string | e.g., "BTC" |
| price | decimal(20,8) | Executed price |
| amount | decimal(20,8) | Traded amount |
| created_at | timestamp | |
| updated_at | timestamp | |

### Entity Relationship Diagram

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   users     │       │   assets    │       │   orders    │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id (PK)     │◄──┬───│ user_id(FK) │       │ user_id(FK) │───┐
│ name        │   │   │ symbol      │       │ symbol      │   │
│ email       │   │   │ amount      │       │ side        │   │
│ balance     │   │   │ locked_amt  │       │ price       │   │
└─────────────┘   │   └─────────────┘       │ amount      │   │
                  │                         │ remaining   │   │
                  │                         │ status      │   │
                  │                         └─────────────┘   │
                  │                                           │
                  │       ┌─────────────┐                     │
                  │       │   trades    │                     │
                  │       ├─────────────┤                     │
                  ├───────│ buyer_id    │◄────────────────────┘
                  └───────│ seller_id   │
                          │ symbol      │
                          │ price       │
                          │ amount      │
                          └─────────────┘
```

## Architecture

### Directory Structure

```
exchange-app/
├── app/
│   ├── Data/                    # Laravel Data DTOs
│   │   ├── Auth/
│   │   ├── Order/
│   │   └── User/
│   ├── Events/                  # Broadcast events
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Api/             # API controllers
│   │       └── Auth/            # Auth controllers
│   ├── Models/                  # Eloquent models
│   ├── Providers/               # Service providers
│   ├── Repositories/            # Repository pattern
│   │   ├── Contracts/           # Repository interfaces
│   │   └── Eloquent/            # Eloquent implementations
│   └── Services/                # Business logic
│       ├── Contracts/           # Service interfaces
│       └── Eloquent/            # Service implementations
├── frontend/                    # Vue.js SPA
│   ├── src/
│   │   ├── Pages/               # Vue page components
│   │   ├── stores/              # Pinia stores
│   │   └── router/              # Vue Router config
│   └── ...
├── docker/                      # Docker configuration
└── ...
```

### Design Patterns

- **Repository Pattern**: Data access abstraction via interfaces
- **Service Pattern**: Business logic encapsulation
- **DTO Pattern**: Using `spatie/laravel-data` for request/response handling
- **Event Broadcasting**: Real-time updates via Laravel Events + Pusher

## Order Matching Logic

1. **Buy Order**: Matches against sell orders with price ≤ buy price (lowest first)
2. **Sell Order**: Matches against buy orders with price ≥ sell price (highest first)
3. **Full Match Only**: Orders only execute when fully matched
4. **Atomic Execution**: Database transactions with `lockForUpdate()` for race safety

### Commission Fee (1.5%)

The commission fee is **1.5% of the trade volume**, deducted from the **seller's USD received**.

**Example:**
```
Trade: 0.01 BTC @ $95,000 = $950 USD volume
Fee: $950 × 0.015 = $14.25 USD

Settlement:
├── Buyer pays:     $950.00 USD
├── Buyer receives: 0.01 BTC (full amount)
├── Seller gives:   0.01 BTC
├── Seller receives: $935.75 USD ($950 - $14.25 fee)
└── Commission:     $14.25 USD (to exchange)
```

This approach ensures:
- Buyer receives the exact asset amount they ordered
- Fee is consistently applied to the seller (USD side)
- Simple and predictable fee structure

## Artisan Commands

### Add Asset to User

Add assets (BTC, ETH, etc.) to a user account by email:

```bash
php artisan app:add-asset <email> <symbol> <amount> [options]
```

**Arguments:**
| Argument | Description |
|----------|-------------|
| `email` | User's email address |
| `symbol` | Asset symbol (e.g., BTC, ETH) |
| `amount` | Amount to add |

**Options:**
| Option | Description |
|--------|-------------|
| `--dry-run` | Preview changes without applying |
| `--force` | Skip confirmation prompt |

**Examples:**

```bash
# Preview only (dry run)
docker-compose exec app php artisan app:add-asset user@example.com BTC 100 --dry-run

# Add with confirmation prompt
docker-compose exec app php artisan app:add-asset user@example.com BTC 100

# Add without confirmation (for scripts/automation)
docker-compose exec app php artisan app:add-asset user@example.com ETH 500 --force

# Invalid symbol shows available options
docker-compose exec app php artisan app:add-asset user@example.com DOGE 100
# ❌ Symbol 'DOGE' not found or is not active.
# Available symbols: BTC, ETH
```

**Sample Output:**
```
📋 Asset Addition Summary
+-----------------+---------------------------+
| Field           | Value                     |
+-----------------+---------------------------+
| User            | John (john@example.com)   |
| User ID         | 1                         |
| Symbol          | BTC (Bitcoin)             |
| Current Balance | 100.00000000              |
| Amount to Add   | +50.00000000              |
| New Balance     | 150.00000000              |
+-----------------+---------------------------+

✅ Successfully added 50 BTC to john@example.com
   New balance: 150 BTC
```

## Running Tests

```bash
docker-compose exec app php artisan test
```

## Docker Services

| Service | Port | Description |
|---------|------|-------------|
| nginx | 8000 | Web server |
| app | 9000 | PHP-FPM |
| db | 3306 | MySQL |
| redis | 6379 | Redis cache |
| reverb | 8090 | WebSocket server (if using Reverb) |

## Environment Variables

Key environment variables in `.env`:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel

# Redis
REDIS_HOST=redis

# Broadcasting
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

## License

MIT License
