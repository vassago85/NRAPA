# GitHub Authentication Setup for Server

## Option 1: Use Personal Access Token (Recommended)

### Step 1: Create a Personal Access Token on GitHub

1. Go to: https://github.com/settings/tokens
2. Click "Generate new token" → "Generate new token (classic)"
3. Give it a name: "NRAPA Server Deployment"
4. Select scopes:
   - ✅ `repo` (Full control of private repositories)
5. Click "Generate token"
6. **Copy the token immediately** (you won't see it again!)

### Step 2: Use Token for Git Operations

**Option A: Use token in URL (one-time)**
```bash
cd /opt/nrapa
git pull https://YOUR_TOKEN@github.com/vassago85/NRAPA.git main
```

**Option B: Store credentials (recommended)**
```bash
cd /opt/nrapa

# Configure git to use credential helper
git config --global credential.helper store

# Pull (will prompt for username and password)
# Username: vassago85
# Password: YOUR_PERSONAL_ACCESS_TOKEN (paste the token, not your password)
git pull origin main
```

**Option C: Use token in remote URL**
```bash
cd /opt/nrapa
git remote set-url origin https://YOUR_TOKEN@github.com/vassago85/NRAPA.git
git pull origin main
```

## Option 2: Use SSH Keys (More Secure)

### Step 1: Generate SSH Key on Server

```bash
ssh-keygen -t ed25519 -C "nrapa-server@yourdomain.com"
# Press Enter to accept default location
# Optionally set a passphrase
```

### Step 2: Add SSH Key to GitHub

```bash
# Display the public key
cat ~/.ssh/id_ed25519.pub
```

1. Copy the output
2. Go to: https://github.com/settings/keys
3. Click "New SSH key"
4. Paste the key
5. Click "Add SSH key"

### Step 3: Update Git Remote to Use SSH

```bash
cd /opt/nrapa
git remote set-url origin git@github.com:vassago85/NRAPA.git
git pull origin main
```

## Option 3: Use GitHub CLI (gh)

```bash
# Install GitHub CLI
sudo apt install gh

# Authenticate
gh auth login

# Then git operations will work
git pull origin main
```

## Quick Fix for Now

**Easiest immediate solution:**

```bash
cd /opt/nrapa

# Create a Personal Access Token first at: https://github.com/settings/tokens
# Then use it like this:

# Method 1: One-time pull with token
git pull https://YOUR_TOKEN@github.com/vassago85/NRAPA.git main

# Method 2: Set remote with token (stored in git config)
git remote set-url origin https://YOUR_TOKEN@github.com/vassago85/NRAPA.git
git pull origin main
```

## Recommended: SSH Setup (Most Secure)

```bash
# 1. Generate SSH key
ssh-keygen -t ed25519 -C "nrapa-server"
# Press Enter twice (no passphrase needed for server)

# 2. Display public key
cat ~/.ssh/id_ed25519.pub

# 3. Copy the output and add to GitHub: https://github.com/settings/keys

# 4. Test connection
ssh -T git@github.com
# Should say: "Hi vassago85! You've successfully authenticated..."

# 5. Update remote
cd /opt/nrapa
git remote set-url origin git@github.com:vassago85/NRAPA.git
git pull origin main
```

## After Authentication Works

Once git pull works, continue with deployment:

```bash
# If using Docker:
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
