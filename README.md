# Laravel Order Management System

A comprehensive Laravel project that handles large CSV order imports with queued processing, workflow management, and KPI generation.

## 🚀 Features

### 1. Queued CSV Import
- **Command**: `php artisan orders:import file.csv`
- Processes large CSV files using queued jobs
- Handles thousands of orders efficiently
- Progress tracking and error handling

### 2. Order Processing Workflow
- **Reserve Stock** → **Simulate Payment** → **Finalize/Rollback**
- Automatic stock management
- Payment simulation with 80% success rate
- Transaction rollback on failures
- Customer statistics updates

### 3. Daily KPIs Generation
- **Command**: `php artisan kpis:generate --date=2024-01-15`
- Revenue tracking
- Order count statistics
- Average Order Value (AOV)
- Cached for performance

### 4. Customer Leaderboard
- **Command**: `php artisan leaderboard:update --limit=10`
- Top customers by total spent
- Real-time ranking updates
- Cached leaderboard data

### 5. Queue Management
- Laravel Horizon alternative (Windows compatible)
- Supervisor configuration provided
- Queue worker management
- Job monitoring and retry logic

## 📁 Project Structure

```
app/
├── Console/Commands/
│   ├── OrdersImportCommand.php      # CSV import command
│   ├── GenerateKPIsCommand.php     # KPI generation
│   └── UpdateLeaderboardCommand.php # Leaderboard updates
├── Jobs/
│   └── ProcessOrderJob.php          # Order processing workflow
└── Models/
    ├── Product.php                  # Product management
    ├── Customer.php                 # Customer data
    ├── Order.php                    # Order processing
    └── OrderItem.php                # Order line items
```

## 🛠️ Installation & Setup

### 1. Database Setup
```bash
# Run migrations
php artisan migrate

# Create sample products
php artisan tinker
>>> Product::create(['name' => 'Product 1', 'sku' => 'PROD001', 'price' => 29.99, 'stock_quantity' => 100]);
```

### 2. Queue Configuration
```bash
# Start queue worker
php artisan queue:work

# Or use the provided batch file
start-worker.bat
```

### 3. Supervisor Setup (Linux/Mac)
```bash
# Copy supervisor configuration
sudo cp supervisor.conf /etc/supervisor/conf.d/laravel-worker.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## 📊 Usage Examples

### Import Orders from CSV
```bash
# Create sample CSV file
echo "customer_name,customer_email,products" > orders.csv
echo "John Doe,john@example.com,[{""sku"":""PROD001"",""quantity"":2}]" >> orders.csv

# Import orders
php artisan orders:import orders.csv
```

### Generate Daily KPIs
```bash
# Generate KPIs for today
php artisan kpis:generate

# Generate KPIs for specific date
php artisan kpis:generate --date=2024-01-15
```

### Update Customer Leaderboard
```bash
# Update top 10 customers
php artisan leaderboard:update

# Update top 25 customers
php artisan leaderboard:update --limit=25
```

## 🔄 Order Processing Workflow

### 1. CSV Import Process
```
CSV File → Parse Rows → Queue Jobs → Process Orders
```

### 2. Order Processing Steps
```
1. Create/Find Customer
2. Create Order Record
3. Reserve Stock (Atomic)
4. Simulate Payment
5. Finalize or Rollback
6. Update Customer Stats
```

### 3. Error Handling
- Stock reservation failures
- Payment simulation failures
- Automatic rollback mechanisms
- Comprehensive logging

## 📈 KPI Metrics

### Daily Metrics
- **Revenue**: Total daily sales
- **Order Count**: Number of orders
- **Average Order Value**: Revenue / Order Count

### Aggregated Metrics
- Yearly totals
- Monthly totals
- Overall system totals

## 🏆 Customer Leaderboard

### Ranking Criteria
- Total amount spent
- Number of orders
- Customer information

### Features
- Real-time updates
- Configurable limits
- Cached performance
- Historical tracking

## ⚙️ Configuration

### Environment Variables
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=assignment_order_system
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_DRIVER=database
```

### Queue Configuration
- **Connection**: Database
- **Timeout**: 300 seconds
- **Retries**: 3 attempts
- **Workers**: 4 processes

## 🚦 Monitoring & Maintenance

### Queue Monitoring
```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Performance Optimization
- Database indexing
- Cached KPI calculations
- Efficient queue processing
- Memory management

## 🔧 Troubleshooting

### Common Issues
1. **Queue not processing**: Check queue worker status
2. **Memory issues**: Reduce batch size in import
3. **Database locks**: Optimize transaction handling
4. **Cache issues**: Clear cache and restart

### Debug Commands
```bash
# Check queue status
php artisan queue:work --verbose

# Monitor logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan cache:clear
```

## 📝 CSV Format

### Required Columns
- `customer_name`: Customer full name
- `customer_email`: Customer email address
- `products`: JSON array of products

### Product JSON Format
```json
[
  {
    "sku": "PROD001",
    "quantity": 2
  },
  {
    "sku": "PROD002", 
    "quantity": 1
  }
]
```

## 🎯 Performance Features

- **Queued Processing**: Handle large datasets
- **Atomic Transactions**: Data consistency
- **Cached Metrics**: Fast KPI generation
- **Efficient Queries**: Optimized database access
- **Error Recovery**: Automatic retry mechanisms

## 📚 API Endpoints (Future Enhancement)

```php
// Get daily KPIs
GET /api/kpis/daily/{date}

// Get customer leaderboard
GET /api/leaderboard

// Get order statistics
GET /api/orders/stats
```

## 🔒 Security Considerations

- Input validation
- SQL injection prevention
- XSS protection
- CSRF tokens
- Rate limiting

## 📊 Monitoring Dashboard (Future Enhancement)

- Real-time queue monitoring
- KPI visualization
- Customer analytics
- Performance metrics
- Error tracking

---

## 🎉 Success!

Your Laravel Order Management System is now fully configured and ready to handle large-scale order processing with:

✅ **Queued CSV Import** - `php artisan orders:import file.csv`  
✅ **Order Workflow** - Reserve → Payment → Finalize/Rollback  
✅ **Daily KPIs** - `php artisan kpis:generate`  
✅ **Customer Leaderboard** - `php artisan leaderboard:update`  
✅ **Queue Management** - Supervisor configuration provided  

The system is production-ready and can handle thousands of orders efficiently!