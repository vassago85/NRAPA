# Fix Migration Issue

The `comments` table migration partially ran and created a duplicate index. Here's how to fix it:

## Option 1: Skip the problematic migration (Recommended)

The migration now checks if the table exists and skips if it does. Just run:

```bash
php artisan migrate --force
```

This will skip the `comments` table creation since it already exists, and continue with the firearm reference migrations.

## Option 2: Rollback and re-run (if Option 1 doesn't work)

If you need to fix the comments table:

```bash
php artisan migrate:rollback --step=1
php artisan migrate --force
```

## Option 3: Manually fix the index (if needed)

If the table exists but is missing columns, you can manually add them or drop the index:

```sql
-- In SQLite (if using database.sqlite):
-- Drop the duplicate index if it exists
DROP INDEX IF EXISTS comments_commentable_type_commentable_id_index;
```

Then run migrations again.

## After Fixing

Once migrations complete, run:

```bash
php artisan nrapa:import-firearm-reference
php artisan optimize:clear
```
