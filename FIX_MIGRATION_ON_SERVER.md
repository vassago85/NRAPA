# Fix Migration Error on Server

## Quick Fix Option 1: Pull Latest Code and Rebuild

The migration has been fixed. You need to pull the latest code and rebuild:

```bash
cd /opt/nrapa
git pull
docker build -t nrapa-app:latest .
docker compose down
docker compose up -d
sleep 5
docker exec nrapa-app php artisan migrate --force
```

## Quick Fix Option 2: Skip This Migration (if foreign key doesn't exist)

If the foreign key doesn't exist, you can manually mark this migration as complete:

```bash
# Connect to database
docker exec -it nrapa-db mysql -unrapa -p'Nrp@2026$Kz9mXvL!' nrapa

# Check if the foreign key exists
SHOW CREATE TABLE calibre_requests;

# If no foreign key exists on calibre_id, mark migration as complete:
INSERT INTO migrations (migration, batch) 
VALUES ('2026_01_30_500000_update_calibre_requests_to_firearm_calibres_framework', 
        (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m))
ON DUPLICATE KEY UPDATE batch = batch;

# Exit MySQL
exit;

# Continue with remaining migrations
docker exec nrapa-app php artisan migrate --force
```

## Quick Fix Option 3: Apply Fix Directly on Server

If you can't rebuild right now, edit the migration file directly on the server:

```bash
cd /opt/nrapa
nano database/migrations/2026_01_30_500000_update_calibre_requests_to_firearm_calibres_framework.php
```

Replace lines 22-25 with:

```php
            // Drop the old foreign key constraint if it exists
            if ($driver === 'mysql') {
                // Check if foreign key exists before dropping (only drop if pointing to 'calibres' table)
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'calibre_requests' 
                    AND COLUMN_NAME = 'calibre_id' 
                    AND REFERENCED_TABLE_NAME = 'calibres'
                ");
                
                if (!empty($foreignKeys)) {
                    foreach ($foreignKeys as $fk) {
                        DB::statement("ALTER TABLE calibre_requests DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    }
                }
            } else {
                // For SQLite, foreign keys are handled differently - will be recreated below
                try {
                    Schema::table('calibre_requests', function (Blueprint $table) {
                        $table->dropForeign(['calibre_id']);
                    });
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
```

Then rebuild and restart:

```bash
docker build -t nrapa-app:latest .
docker compose restart nrapa-app
docker exec nrapa-app php artisan migrate --force
```

## Recommended: Use Option 1

The cleanest approach is Option 1 - pull the latest code and rebuild. The migration has been fixed in the repository.
