# EcoCycle Deployment

The repo is prepared for two hosting paths:

- Render: recommended for production because it runs the full Laravel app in Docker with PostgreSQL and a queue worker.
- Vercel: best-effort demo support through the community PHP runtime. Vercel does not run Docker and does not officially support Laravel/PHP as a first-class runtime.

## Render

1. Push the repo to GitHub.
2. In Render, create a new Blueprint from this repository. Render reads `render.yaml`.
3. Create the required secret values when Render prompts you:
   - `APP_KEY`: generate locally with `php artisan key:generate --show`
   - `APP_URL`: your Render web URL, for example `https://ecocycle-web.onrender.com`
   - `ADMIN_EMAIL`: your admin login email
   - `ADMIN_PASSWORD`: a strong admin password
4. Deploy the Blueprint.

`render.yaml` creates:

- `ecocycle-web`: Laravel web service
- `ecocycle-worker`: queue worker
- `ecocycle-db`: PostgreSQL database

Render runs migrations before deploy and seeds the first admin account after the first successful deploy.

Important production notes:

- Keep `DEMO_LOGIN_ENABLED=false`.
- Add Cloudinary credentials if users need persistent avatar uploads:
  - `CLOUDINARY_CLOUD_NAME`
  - `CLOUDINARY_API_KEY`
  - `CLOUDINARY_API_SECRET`
- Configure real mail before password reset or OTP email is needed:
  - `MAIL_MAILER`
  - `MAIL_HOST`
  - `MAIL_PORT`
  - `MAIL_USERNAME`
  - `MAIL_PASSWORD`
  - `MAIL_FROM_ADDRESS`

## Vercel

Vercel support is configured with:

- `vercel.json`
- `api/index.php`
- `api/php.ini`
- Composer script `vercel`

Required Vercel project environment variables:

```env
APP_KEY=base64:...
APP_URL=https://your-project.vercel.app
DEMO_LOGIN_ENABLED=false
DB_CONNECTION=pgsql
DB_URL=your-external-postgres-connection-string
ADMIN_EMAIL=youradmin@email.com
ADMIN_PASSWORD=YourStrongPassword123
```

Recommended Vercel environment variables:

```env
MAIL_MAILER=log
LOG_LEVEL=error
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
```

After first Vercel deploy, run migrations and seed against the same remote database from your local machine:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Vercel limitations for this app:

- No persistent Laravel queue worker, so `QUEUE_CONNECTION=sync` is used.
- No persistent local filesystem, so configure Cloudinary for uploaded avatars.
- Use an external managed PostgreSQL/MySQL database. Do not use SQLite.
