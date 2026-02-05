# Switch from SQLite to MySQL

## ✅ Configuration Updated

Your `.env` file has been updated to use MySQL instead of SQLite.

## 📋 Setup Steps

### 1. Ensure Laragon MySQL/MariaDB is Running

- Open Laragon
- Click "Start All" or ensure MySQL/MariaDB is running (green indicator)
- If not running, click the MySQL/MariaDB service to start it

### 2. Create the Database

Open Laragon's MySQL terminal or use HeidiSQL/phpMyAdmin, then run:

```sql
CREATE DATABASE IF NOT EXISTS NRAPA CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Or via command line:**
```powershell
# In Laragon terminal
mysql -u root -e "CREATE DATABASE IF NOT EXISTS NRAPA CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Clear Laravel Config Cache

```powershell
cd c:\laragon\www\NRAPA
php artisan config:clear
php artisan cache:clear
```

### 4. Test Database Connection

```powershell
php artisan tinker
# Then in tinker:
DB::connection()->getPdo();
# Should return: PDO object without errors
```

### 5. Run Migrations

```powershell
php artisan migrate
```

### 6. Import Firearm Reference Data

```powershell
php artisan nrapa:import-firearm-reference
```

### 7. Seed Initial Data (Optional)

```powershell
php artisan db:seed
```

## 🔄 Migrating Existing SQLite Data (If Needed)

If you have important data in your SQLite database that you want to keep:

### Option A: Export SQLite and Import to MySQL

1. **Export SQLite data:**
   ```powershell
   # Install sqlite3 if needed, then:
   sqlite3 database/database.sqlite .dump > sqlite_export.sql
   ```

2. **Convert SQL syntax** (SQLite → MySQL):
   - Remove SQLite-specific syntax
   - Convert AUTOINCREMENT to AUTO_INCREMENT
   - Adjust data types if needed

3. **Import to MySQL:**
   ```powershell
   mysql -u root NRAPA < converted_export.sql
   ```

### Option B: Start Fresh (Recommended for Development)

If you're okay starting fresh:
- Just run migrations and seeders
- Your seeders will populate initial data

## ✅ Verify Everything Works

1. **Check database connection:**
   ```powershell
   php artisan tinker
   DB::select('SELECT DATABASE()');
   ```

2. **Check tables exist:**
   ```powershell
   php artisan tinker
   Schema::hasTable('users');
   Schema::hasTable('firearm_calibres');
   ```

3. **Test the application:**
   - Visit `http://nrapa.test` (or your local URL)
   - Login and verify everything works

## 🎯 Benefits of MySQL

- ✅ **Production Parity**: Matches your production environment
- ✅ **Proper ENUM Support**: No CHECK constraint workarounds
- ✅ **Better Performance**: Especially with larger datasets
- ✅ **Full SQL Features**: Stored procedures, triggers, views
- ✅ **Concurrent Access**: Better for multiple users
- ✅ **Laragon Integration**: Native support

## 🐛 Troubleshooting

### "Access denied for user 'root'@'localhost'"

Check your MySQL password in Laragon settings. Update `.env`:
```
DB_PASSWORD=your_password
```

### "Unknown database 'NRAPA'"

Create the database (Step 2 above).

### "Connection refused"

Ensure MySQL/MariaDB is running in Laragon.

### Migration Errors

If you get migration errors:
```powershell
php artisan migrate:fresh  # ⚠️ WARNING: This drops all tables
php artisan migrate
php artisan nrapa:import-firearm-reference
```

## 📝 Notes

- Your SQLite database file (`database/database.sqlite`) will remain but won't be used
- You can delete it later if you want: `del database\database.sqlite`
- All future migrations will work properly with MySQL's ENUM support
