# Database Performance & Indexing Strategy

## Overview
This document outlines the database indexing strategy for the GreenTrip API to ensure optimal performance for common query patterns.

## Index Strategy

### Bookings Table
**Purpose**: Store user flight bookings with emissions data

**Key Indexes**:
- `bookings_user_created_at_index` (user_id, created_at)
  - **Use Case**: User booking history, emissions reporting
  - **Query Pattern**: `WHERE user_id = ? ORDER BY created_at DESC`
  - **Performance Impact**: High - used for all user-specific queries

- `bookings_emissions_reporting_index` (user_id, created_at, emissions)
  - **Use Case**: Emissions summary calculations
  - **Query Pattern**: `WHERE user_id = ? AND created_at BETWEEN ? AND ?`
  - **Performance Impact**: Critical - used for cached emissions reports

- `bookings_user_status_index` (user_id, status)
  - **Use Case**: Filtering bookings by status
  - **Query Pattern**: `WHERE user_id = ? AND status = ?`
  - **Performance Impact**: Medium - used for booking management

### Flight Details Table
**Purpose**: Store flight information for search and booking

**Key Indexes**:
- `flight_details_route_date_index` (from, to, date)
  - **Use Case**: Flight search by route and date
  - **Query Pattern**: `WHERE from = ? AND to = ? AND date = ?`
  - **Performance Impact**: Critical - used for all flight searches

- `flight_details_search_price_index` (from, to, date, price)
  - **Use Case**: Flight search with price filtering
  - **Query Pattern**: `WHERE from = ? AND to = ? AND date = ? AND price <= ?`
  - **Performance Impact**: High - used for price-optimized searches

- `flight_details_search_eco_index` (from, to, date, eco_rating)
  - **Use Case**: Eco-friendly flight filtering
  - **Query Pattern**: `WHERE from = ? AND to = ? AND date = ? ORDER BY eco_rating DESC`
  - **Performance Impact**: Medium - used for sustainability features

### Users Table
**Purpose**: User authentication and profile management

**Key Indexes**:
- `users_email_index` (email)
  - **Use Case**: User login and email lookups
  - **Query Pattern**: `WHERE email = ?`
  - **Performance Impact**: Critical - used for every login attempt

- `users_email_verification_index` (email, email_verified_at)
  - **Use Case**: Email verification status checks
  - **Query Pattern**: `WHERE email = ? AND email_verified_at IS NOT NULL`
  - **Performance Impact**: High - used for authentication flow

## Query Optimization Guidelines

### 1. Use Indexed Scopes
```php
// ✅ Optimized - uses bookings_user_created_at_index
Booking::forUser($userId)->get();

// ✅ Optimized - uses bookings_emissions_reporting_index
Booking::forEmissionsReport($userId, $startDate, $endDate)->get();
```

### 2. Avoid N+1 Queries
```php
// ✅ Optimized - eager loading with indexed relationships
Booking::with(['flightDetail', 'passengers'])->forUser($userId)->get();

// ❌ Avoid - causes N+1 queries
$bookings = Booking::forUser($userId)->get();
foreach ($bookings as $booking) {
    $booking->flightDetail; // Additional query per booking
}
```

### 3. Leverage Composite Indexes
```php
// ✅ Optimized - uses flight_details_route_date_index
FlightDetail::where('from', 'JFK')
    ->where('to', 'LAX')
    ->where('date', '2024-01-15')
    ->get();

// ❌ Avoid - can't use composite index effectively
FlightDetail::where('from', 'JFK')
    ->orWhere('to', 'LAX') // Breaks index usage
    ->get();
```

## Performance Monitoring

### Key Metrics to Monitor
1. **Query Execution Time**: Target < 100ms for indexed queries
2. **Index Usage**: Monitor via `SHOW INDEX FROM table_name`
3. **Slow Query Log**: Review queries taking > 1 second
4. **Cache Hit Rate**: Target > 90% for emissions reports

### Monitoring Queries
```sql
-- Check index usage
SHOW INDEX FROM bookings;

-- Analyze query performance
EXPLAIN SELECT * FROM bookings WHERE user_id = 1 ORDER BY created_at DESC;

-- Monitor slow queries
SHOW VARIABLES LIKE 'slow_query_log';
```

## Future Optimizations

### Planned Indexes
1. **Geographic Indexes**: For airport proximity searches
2. **Full-Text Search**: For airline name searches
3. **Partitioning**: By date for large booking tables

### Performance Targets
- **API Response Time**: < 200ms for 95% of requests
- **Database Query Time**: < 50ms for indexed queries
- **Cache Hit Rate**: > 95% for frequently accessed data

## Maintenance

### Regular Tasks
1. **Index Analysis**: Monthly review of index usage
2. **Query Optimization**: Quarterly performance review
3. **Index Rebuilding**: As needed for fragmented indexes

### Commands
```bash
# Analyze table performance
php artisan db:monitor

# Optimize tables
php artisan db:optimize

# Check index fragmentation
php artisan db:analyze-indexes
```