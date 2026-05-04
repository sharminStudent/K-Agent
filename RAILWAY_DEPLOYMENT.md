# Railway Deployment Checklist

This app can go live on Railway without a separate WebSocket service for the first launch.
The widget already falls back to the normal HTTP response path when Reverb is not configured.

## 1. GitHub Actions branch

Auto-deploy now supports both `main` and `master`, and deploy waits for the `Tests` workflow to succeed first.

## 2. Railway environment values

Start from [.env.railway.example](.env.railway.example).

Minimum required variables:

- `APP_KEY`
- `APP_URL`
- `DB_CONNECTION=pgsql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_SSLMODE=require`
- `OPENAI_API_KEY`
- `OPENAI_CHAT_MODEL`
- `OPENAI_EMBEDDING_MODEL`

Recommended first-launch values:

- `SEED_DEMO_CONTENT=true` if you want the demo academy page created automatically
- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=sync`
- `BROADCAST_CONNECTION=null`

Why:

- `QUEUE_CONNECTION=sync` avoids needing a separate worker service for launch day.
- `BROADCAST_CONNECTION=null` avoids needing a separate Reverb service for launch day.
- The widget still works because it already falls back to the direct API response path.

## 3. Railway service behavior

Current deploy config in `railway.json`:

- runs `php artisan migrate --force` before deploy
- runs `php artisan db:seed --force` before deploy
- checks app health at `/up`

The seeder is safe for production because:

- the base `DatabaseSeeder` only creates demo content when `SEED_DEMO_CONTENT=true`
- the dummy client seeder uses `updateOrCreate`, so it is idempotent
- no generic production test user is created unless the app is running locally

## 4. First live smoke checks

After the first deploy, verify:

1. `GET /up` returns `200`
2. `GET /admin/login` loads
3. widget preview loads:
   `https://your-domain/widget/{widget_token}/preview`
4. dummy client page loads:
   `https://your-domain/dummy-client/{agent-slug}`
5. sending a chat message returns an assistant reply

If `SEED_DEMO_CONTENT=true`, the built-in demo tenant will be:

- dummy page:
  `https://your-domain/dummy-client/brightpath-academy`
- widget preview:
  `https://your-domain/widget/brightpath-academy-widget-demo-token-2026/preview`

## 5. Optional later upgrades

Once the base launch is stable, you can add:

- a dedicated queue worker service and switch `QUEUE_CONNECTION` to `database`
- a dedicated Reverb service and switch `BROADCAST_CONNECTION` to `reverb`
- a real SMTP provider instead of `MAIL_MAILER=log`
