# NEXT Ventures - Software Engineer Level 2 Backend Assessment

A comprehensive Laravel backend system implementing order processing, notifications, and refund management with queue-based architecture.

## Assignment Overview

### Task 1: Order Processing & Analytics
- CSV Import: Large CSV order import using queued commands
- Order Workflow: Reserve stock → Simulate payment → Finalize/Rollback
- Daily KPIs: Revenue, order count, average order value using Cache
- Leaderboard: Top customers ranking system
- Queue Management: Laravel Horizon + Supervisor configuration

### Task 2: Order Notifications
- Notification System: Email/log notifications for order processing
- Queued Jobs: Non-blocking notification processing
- Required Data: order_id, customer_id, status, total included
- History Storage: Separate notifications table with complete tracking

### Task 3: Refund Handling & Analytics
- Refund Processing: Partial and full refund handling
- Asynchronous Jobs: Queued refund processing with retry logic
- Real-time Updates: KPIs and leaderboard updates in real-time
- Idempotency: Complete protection against double-counting and data corruption

## System Architecture

### Backend-Only Implementation
- Framework: Laravel 11 with PHP 8.2+
- Database: MySQL with comprehensive schema
- Queue System: Laravel Queue with Horizon + Supervisor
- Cache: Laravel Cache for KPIs and leaderboard
- CLI Interface: Command-line operations only

### Core Components
- Models: Customer, Order, OrderItem, Product, Refund, Notification
- Jobs: ProcessOrderJob, ProcessRefundJob, SendOrderNotificationJob
- Commands: 8 CLI commands for all operations
- Migrations: 10 database migrations for complete schema

## Quick Start

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Laravel CLI

### Installation
```bash
# Clone repository
git clone https://github.com/Poornima1996/Customer_order_system.git
cd Customer_order_system

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database configuration
# Update .env with your MySQL credentials
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=assignment_order_system
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Start queue worker (Windows)
start-worker.bat

# Or manually
php artisan queue:work --sleep=3 --tries=3
```

## Usage Examples

### Task 1: Order Processing
```bash
# Import orders from CSV
php artisan orders:import sample_orders.csv

# Generate daily KPIs
php artisan kpis:generate

# Update customer leaderboard
php artisan leaderboard:update
```

### Task 2: Notifications
```bash
# Test notification system
php artisan notifications:test --order-id=1 --type=success --channel=log

# Test email notifications
php artisan notifications:test --order-id=1 --type=success --channel=email
```

### Task 3: Refunds
```bash
# Process partial refund
php artisan refund:process 1 25.00 --type=partial --reason="Customer complaint"

# Process full refund
php artisan refund:process 1 99.99 --type=full --reason="Order cancellation"

# Check refund status
php artisan refund:status --order-id=1

# Test refund system
php artisan refund:test --demo
```

## Database Schema

### Core Tables
- products: Product catalog with stock management
- customers: Customer information with spending statistics
- orders: Order records with status tracking
- order_items: Order line items with product relationships
- refunds: Refund tracking with partial/full support
- notifications: Notification history with status tracking

### Key Relationships
- Customer → Orders (One-to-Many)
- Order → OrderItems (One-to-Many)
- Order → Refunds (One-to-Many)
- Order → Notifications (One-to-Many)

## Configuration

### Queue Configuration
```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'database'),
```

### Cache Configuration
```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'file'),
```

### Supervisor Configuration
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php C:\New_Orders_System\artisan queue:work --sleep=3 --tries=3 --daemon
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=C:\New_Orders_System\worker.log
```

## Testing

### Sample Data
```csv
customer_name,customer_email,phone,address,products
John Doe,john.doe@example.com,123-456-7890,"123 Main St, Anytown",[{"sku":"PROD001","quantity":2},{"sku":"PROD002","quantity":1}]
Jane Smith,jane.smith@example.com,098-765-4321,"456 Oak Ave, Otherville",[{"sku":"PROD003","quantity":3}]
Bob Johnson,bob.j@example.com,111-222-3333,"789 Pine Ln, Somewhere",[{"sku":"PROD001","quantity":1},{"sku":"PROD003","quantity":1}]
```

### Test Commands
```bash
# Complete system test
php artisan orders:import sample_orders.csv
php artisan kpis:generate
php artisan leaderboard:update
php artisan notifications:test --order-id=1 --type=success --channel=log
php artisan refund:process 1 25.00 --type=partial --reason="Test refund"
php artisan refund:status --order-id=1
```

## Performance Features

### Queue Processing
- Asynchronous order processing
- Non-blocking notification system
- Scalable refund processing
- Configurable retry mechanisms

### Real-time Analytics
- Live KPI updates
- Dynamic leaderboard recalculation
- Cache-optimized performance
- Database transaction safety

### Error Handling
- Comprehensive validation
- Transaction rollback support
- Detailed error logging
- Graceful failure handling

## Available Commands

### Order Management
- `php artisan orders:import {file}` - Import orders from CSV
- `php artisan kpis:generate {--date=}` - Generate daily KPIs
- `php artisan leaderboard:update {--limit=10}` - Update customer leaderboard

### Notification System
- `php artisan notifications:test {--order-id=} {--type=} {--channel=}` - Test notifications

### Refund System
- `php artisan refund:process {order_id} {amount} {--type=} {--reason=} {--notes=}` - Process refunds
- `php artisan refund:status {--order-id=} {--refund-id=}` - Check refund status
- `php artisan refund:test {--order-id=} {--demo}` - Test refund system

### Queue Management
- `php artisan queue:work` - Process queued jobs
- `start-worker.bat` - Start queue worker (Windows)

## Project Structure

```
app/
├── Console/Commands/          # CLI Commands (8 files)
├── Jobs/                     # Queue Jobs (3 files)
├── Models/                   # Eloquent Models (5 files)
└── Providers/               # Service Providers

database/migrations/          # Database Migrations (10 files)
config/                       # Laravel Configuration
storage/                      # Laravel Storage
public/                       # Public Assets
```

## Assignment Completion Status

### Task 1: Order Processing & Analytics
- CSV import with queued processing
- Order workflow (reserve → payment → finalize)
- Daily KPIs generation (revenue, order count, AOV)
- Customer leaderboard with rankings
- Queue management with Supervisor

### Task 2: Order Notifications
- Notification system (email/log channels)
- Queued notification jobs
- Required data inclusion (order_id, customer_id, status, total)
- Notification history storage

### Task 3: Refund Handling & Analytics
- Refund processing (partial/full)
- Asynchronous refund jobs
- Real-time KPI updates
- Complete idempotency protection

## Technical Highlights

### Backend Architecture
- Laravel 11 with modern PHP practices
- Queue-based processing for scalability
- Comprehensive database design
- CLI-only interface (no UI)

### Business Logic
- Complete order lifecycle management
- Advanced refund handling
- Real-time analytics and reporting
- Customer relationship management

### Technical Excellence
- Production-ready code quality
- Comprehensive error handling
- Transaction safety
- Performance optimization

## Support

For questions about this implementation:
- Repository: https://github.com/Poornima1996/Customer_order_system.git
- Documentation: Complete implementation guides included
- Testing: All commands tested and verified

**NEXT Ventures Software Engineer Level 2 Backend Assessment - Complete Implementation**