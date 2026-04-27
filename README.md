# SuppTracker

SuppTracker is a Laravel 12 + Inertia React application for tracking the daily work of an applications support team.

## What it does

- Admins maintain the master list of activities.
- Team members update each activity with a status and remark.
- Each update stores the updater snapshot, date, and time for handover visibility.
- Managers can review daily handovers and filter historical reports by date range, activity, personnel, and status.

## Local setup

```bash
composer install
npm install
php artisan migrate --seed
npm run dev
```

If you prefer the full Laravel dev workflow, use:

```bash
composer run dev
```

## Seed credentials

- Admin: `admin@supptracker.com`
- Password: `password`

## Role behavior

- `admin` can create, edit, and delete activities.
- `member` can update activity status and add remarks.
- Both roles can view the daily handover and reporting screens.

## Deployment notes

### Development (SQLite)

This project uses SQLite by default for local development.

### Production (Turso)

For production deployment with [Turso](https://turso.tech) (remote SQLite):

1. **Create a Turso database**

    ```bash
    turso db create supptracker
    ```

2. **Get your credentials**

    ```bash
    turso db show --url supptracker
    turso db tokens create supptracker
    ```

3. **Configure environment variables**

    ```env
    DB_CONNECTION=libsql
    DB_URL=libsql://your-db-name.aws-ap-northeast-1.turso.io
    TURSO_AUTH_TOKEN=your-auth-token
    ```

   Seeded admin login for the demo database:

   - Email: `admin@supptracker.com`
   - Password: `password`

4. **Run migrations on Turso**

    ```bash
    php artisan migrate --force
    php artisan db:seed --force
    ```

5. **Deploy**
    - Push to GitHub
    - Configure CI/CD to run migrations
    - Deploy to your hosting platform (Laravel Cloud, Vercel, Heroku, etc.)

**Requirements:**

- PHP 8.3+ with FFI extension (for SDK-based connection)
- Or PHP 8.2+ (HTTP API support planned)

### General deployment

- Configure production environment variables: `APP_URL`, `APP_ENV`, database credentials.
- Run migrations and asset builds in the deployment pipeline.
- Keep `composer run dev` for local development only.
- Install the Laravel Cloud CLI after upgrading PHP if the environment requires it.

## Suggested demo flow

1. Log in as the seeded admin user.
2. Create or review activities.
3. Log in as a team member and update statuses with remarks.
4. Open the daily handover board to review the day’s timeline.
5. Open reports and filter by date, activity, personnel, and status.

## Screenshots

Add screenshots here for:

- Login page
- Activities page
- Daily handover page
- Reports page

## Testing

```bash
php artisan test --compact
```
