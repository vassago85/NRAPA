# CI Debugging Analysis

## Findings

### 1. Composer.json Analysis
✅ **No git-based repositories directly configured** - All packages come from Packagist
✅ **`preferred-install: "dist"` is set** in composer.json config (line 95)
✅ **All packages have both git source and dist zip options** in composer.lock

### 2. Composer.lock Analysis
- Found **30+ packages** with git source URLs (all are public GitHub repos)
- **All packages also have dist/zip fallbacks** available
- Examples of git-based packages:
  - `aws/aws-crt-php` → https://github.com/awslabs/aws-crt-php.git
  - `aws/aws-sdk-php` → https://github.com/aws/aws-sdk-php.git
  - `brick/math` → https://github.com/brick/math.git
  - And many more...

### 3. Potential Issues

#### Issue 1: Composer might be falling back to git
Even with `--prefer-dist`, if the dist download fails, composer might try git as fallback. If git is misconfigured, this causes exit code 128.

#### Issue 2: Git authentication prompts
Some packages might require git access for metadata even when using dist.

#### Issue 3: Composer cache issues
If composer cache is corrupted, it might try git instead of dist.

## Recommendations

### Solution 1: Force dist-only installation (Recommended)
Add `--no-dev` temporarily or ensure `--prefer-dist` is working. But wait - we need dev dependencies for tests!

Actually, let's try a different approach - ensure composer NEVER uses git:

```yaml
- name: Install Dependencies
  env:
    GIT_TERMINAL_PROMPT: 0
    GIT_ASKPASS: echo
    COMPOSER_DISABLE_XDEBUG_WARN: 1
    COMPOSER_PREFER_DIST: 1
  run: |
    composer config --no-plugins allow-plugins.pestphp/pest-plugin true
    composer config --no-plugins allow-plugins.php-http/discovery true
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts || {
      echo "=== Composer install failed ==="
      echo "=== Last 50 lines of output ==="
      tail -50 composer-install.log || true
      echo "=== Composer diagnose ==="
      composer diagnose || true
      echo "=== Checking composer cache ==="
      composer clear-cache || true
      exit 1
    }
```

### Solution 2: Clear composer cache before install
Add a step to clear composer cache:

```yaml
- name: Clear Composer Cache
  run: composer clear-cache || true
```

### Solution 3: Check if specific package is causing issues
The error might be from a specific package. We can identify it by checking which package composer is trying to install when it fails.

## Next Steps

1. **Check the CI logs** - Look for which specific package composer is trying to install when git fails
2. **Try Solution 1** - Force dist-only and clear cache
3. **If still failing** - Check if there's a specific package causing issues (might be AWS SDK or another large package)

## What to Look For in CI Logs

When the next CI run fails, look for:
1. **Which package** composer is installing when git fails
2. **The exact git command** that's failing
3. **Whether dist downloads are working** or if composer is skipping to git

## Test Commands (Run Locally)

```bash
# Test composer install with dist-only
composer install --prefer-dist --no-interaction -vvv

# Check if any packages require git
composer show --installed | grep -i git

# Clear cache and retry
composer clear-cache
composer install --prefer-dist --no-interaction
```
