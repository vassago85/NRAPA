# Server Deployment - Fixing Common Issues

## Issues Found

1. **Git SSH Permission Denied** - Need to use HTTPS or set up SSH keys
2. **Composer/PHP/NPM not in PATH** - Need to find their locations or activate environment

## Step-by-Step Fix

### 1. Fix Git Pull (Use HTTPS instead of SSH)

```bash
# Check current remote URL
git remote -v

# If it shows git@github.com, change to HTTPS:
git remote set-url origin https://github.com/vassago85/NRAPA.git

# Or if you have SSH keys set up, check them:
ls -la ~/.ssh/
```

### 2. Find PHP/Composer/NPM Locations

```bash
# Find PHP
which php
# Or check common locations:
/usr/bin/php
/usr/local/bin/php
/opt/php/bin/php
# Or if using Laragon/Docker:
docker exec nrapa-app php --version

# Find Composer
which composer
# Or check:
/usr/local/bin/composer
~/.composer/vendor/bin/composer
# Or if using Docker:
docker exec nrapa-app composer --version

# Find NPM
which npm
# Or check:
/usr/bin/npm
/usr/local/bin/npm
# Or if using Docker:
docker exec nrapa-app npm --version
```

### 3. If Using Docker (Most Likely)

If your application runs in Docker containers, use:

```bash
# Pull changes (on host)
cd /opt/nrapa
git remote set-url origin https://github.com/vassago85/NRAPA.git
git pull origin main

# Then run commands inside container
docker exec nrapa-app composer install --no-interaction --prefer-dist --optimize-autoloader
docker exec nrapa-app php artisan migrate --force
docker exec nrapa-app php artisan optimize:clear
docker exec nrapa-app npm install
docker exec nrapa-app npm run build

# Or if you have docker-compose:
docker-compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan optimize:clear
docker-compose exec app npm install
docker-compose exec app npm run build
```

### 4. If Using Laragon/Standalone PHP

```bash
# Add to PATH (temporary for this session)
export PATH=$PATH:/usr/local/bin:/opt/php/bin:~/.composer/vendor/bin

# Or use full paths:
/usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader
/usr/bin/php artisan migrate --force
/usr/bin/php artisan optimize:clear
/usr/bin/npm install
/usr/bin/npm run build
```

### 5. Check Your Setup

```bash
# Check if Docker is being used
docker ps
# Or
docker-compose ps

# Check for docker-compose.yml
ls -la docker-compose.yml

# Check for Dockerfile
ls -la Dockerfile
```

## Recommended Approach

Based on your path `/opt/nrapa`, you're likely using Docker. Try this:

```bash
cd /opt/nrapa

# Fix git remote to use HTTPS
git remote set-url origin https://github.com/vassago85/NRAPA.git

# Pull changes
git pull origin main

# Check if Docker is running
docker ps

# If Docker container is named 'nrapa-app' or similar:
docker exec nrapa-app composer install --no-interaction --prefer-dist --optimize-autoloader
docker exec nrapa-app php artisan migrate --force
docker exec nrapa-app php artisan optimize:clear
docker exec nrapa-app npm install
docker exec nrapa-app npm run build

# Or if using docker-compose:
docker-compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan optimize:clear
docker-compose exec app npm install
docker-compose exec app npm run build
```

## Alternative: Use Deployment Script

If you have a deployment script, check:

```bash
ls -la deploy*.sh
ls -la deploy*.php
```

## Quick Diagnostic Commands

Run these to understand your setup:

```bash
# Check for Docker
docker --version
docker ps

# Check for docker-compose
docker-compose --version
docker-compose ps

# Check git remote
git remote -v

# Check for PHP/Composer/NPM in common locations
ls -la /usr/bin/php* /usr/local/bin/php* /usr/bin/composer /usr/local/bin/composer /usr/bin/npm /usr/local/bin/npm 2>/dev/null

# Check environment
echo $PATH
which php composer npm
```
