# GitHub SSH key setup (for server / Linux)

Use this when `git pull` asks for "Username for 'https://github.com'" and you want to use SSH instead.

## 1. Check for an existing SSH key

On the server, run:

```bash
ls -la ~/.ssh
```

Look for `id_ed25519` and `id_ed25519.pub` (or `id_rsa` / `id_rsa.pub`). If you see the `.pub` file, skip to step 3.

## 2. Generate a new SSH key (if needed)

```bash
ssh-keygen -t ed25519 -C "your_email@example.com" -f ~/.ssh/id_ed25519 -N ""
```

- Use your real email (or a label like `nrapa-server`).
- `-N ""` means no passphrase (so `git pull` is non-interactive). For better security use a passphrase and an ssh-agent.

## 3. Show your public key (to add in GitHub)

```bash
cat ~/.ssh/id_ed25519.pub
```

Copy the full line (starts with `ssh-ed25519` and ends with your email or comment).

## 4. Add the key in GitHub

1. Open **GitHub** → **Settings** → **SSH and GPG keys**  
   (or go to https://github.com/settings/keys)
2. Click **New SSH key**.
3. **Title:** e.g. `NRAPA server` or `cha021-truserv1230`.
4. **Key type:** Authentication Key.
5. **Key:** paste the contents of `~/.ssh/id_ed25519.pub`.
6. Click **Add SSH key**.

## 5. Switch the repo to SSH and test

On the server, in the repo directory (e.g. `/opt/nrapa`):

```bash
# See current remote
git remote -v

# Change origin from HTTPS to SSH (use your actual GitHub repo path)
git remote set-url origin git@github.com:vassago85/NRAPA.git

# Test SSH and then pull
ssh -T git@github.com
git pull origin main
```

If `ssh -T git@github.com` says "Hi username! You've successfully authenticated...", the key is set up correctly.

## 6. If the repo URL is different

If your repo is under a different org/user, replace in the URL:

```bash
git remote set-url origin git@github.com:OWNER/REPO.git
```

---

**Quick copy-paste (after generating key and adding it to GitHub):**

```bash
cd /opt/nrapa
git remote set-url origin git@github.com:vassago85/NRAPA.git
ssh -T git@github.com
git pull origin main
```
