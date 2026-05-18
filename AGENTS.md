# Agent Notes

## Production deployment

- **Host**: `cha021-truserv1230-jhb1-001` (Ubuntu, user `paul`)
- **Project folder on host**: `/opt/nrapa`
- **Runtime**: Everything runs in Docker. PHP, Composer, Node/npm are **not** installed on the host — all artisan/composer/npm commands must run inside containers via `docker compose exec <service> ...`.
- **Repo**: https://github.com/vassago85/nrapa (origin `main`)
- **Helpful files in repo root**: `deploy.sh`, `REBUILD_COMMANDS.md`, `OPERATIONS.md`, `DEPLOYMENT.md` — check these first for existing automation before hand-rolling commands.

### Docker services (from `docker compose ps`)

| Service     | Container         | Image                   | Role                                  |
|-------------|-------------------|-------------------------|---------------------------------------|
| `app`       | `nrapa-app`       | `nrapa-app:latest`      | Main PHP/nginx + node/npm (artisan, composer, npm all run here) |
| `queue`     | `nrapa-queue`     | `nrapa-app:latest`      | `php artisan queue:work`              |
| `scheduler` | `nrapa-scheduler` | `nrapa-app:latest`      | Cron/scheduler loop                   |
| `db`        | `nrapa-db`        | `mysql:8.0`             | MySQL database                        |
| `redis`     | `nrapa-redis`     | `redis:alpine`          | Cache/queue broker                    |
| `minio`     | `nrapa-minio`     | `minio/minio:latest`    | S3-compatible object storage          |
| `gotenberg` | `nrapa-gotenberg` | `gotenberg/gotenberg:8` | PDF rendering service                 |

App is exposed on host port **8085** (`0.0.0.0:8085->80/tcp`).

### Deploy / rebuild flow — CRITICAL

**The `Dockerfile` bakes the source into the image with `COPY . .` — there is NO bind mount of `/opt/nrapa` into the container.** This means `git pull` on the host updates the working tree but the running container keeps serving the old code until the image is rebuilt.

**Every deploy MUST rebuild the image.** `docker compose exec` / `restart` / `optimize:clear` alone will not pick up new code.

Correct sequence from `/opt/nrapa` on the host:

```bash
git pull
docker compose build app
docker compose up -d app queue scheduler
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
docker compose exec app php artisan view:cache
```

Notes:
- `docker compose build app` rebuilds the `nrapa-app:latest` image used by `app`, `queue`, and `scheduler`. **`npm install` and `npm run build` happen inside this step** (see `Dockerfile` line ~57) and `node_modules` is then deleted to keep the image small. The compiled assets live in `public/build/`.
- `docker compose up -d app queue scheduler` recreates those containers against the new image.
- Do **NOT** run `docker compose exec app npm run build` afterwards — it will fail with `vite: not found` because `node_modules` is already gone from the running image. The build already ran during `docker compose build`.
- `composer install` is already run inside the Dockerfile build step — only run it manually via `exec` if you've changed deps and need them re-resolved *outside* a rebuild (rare).

Symptom that tells you you've forgotten to rebuild: `git log` on host shows the new commit, but `docker compose exec app grep …` against the source file inside the container still shows the old content. Always rebuild.

Check `/opt/nrapa/deploy.sh` and `/opt/nrapa/REBUILD_COMMANDS.md` for any project-specific automation before hand-rolling commands.

Never suggest running `composer`, `php artisan`, or `npm` directly on the host — they'll fail with "command not found".

## Local dev

- Windows + Laragon, project at `c:\laragon\www\NRAPA`.
- PowerShell shell — no bash heredocs, no `&&` between commands (use `;`). For multi-line git commit messages, write to a temp file and `git commit -F <absolute path>`.
