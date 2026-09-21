# Local Docker — Real Working

One command to keep local working (pgsql) — Railway uses same pgsql via DATABASE_URL, no Docker needed on prod.

```bash
# start postgres (first time)
docker run --name frontdesk-pg -e POSTGRES_PASSWORD=secret -e POSTGRES_DB=laravel -e POSTGRES_USER=root -p 5432:5432 -d postgres:16

# or if exists:
docker start frontdesk-pg

# migrate + seed 4 portals
php artisan migrate --force
php artisan db:seed --class=PortalUsersSeeder --force

# serve
php artisan serve --host=127.0.0.1 --port=8000
npm run dev # if editing login
```

Login: http://127.0.0.1:8000/login — half logo half form.
Demo: reception@jobarn.co.tz / it@jobarn.co.tz / sales@jobarn.co.tz / manager@jobarn.co.tz / admin@jobarn.co.tz — password 12345678 — auto-routes per role.

Stop: docker stop frontdesk-pg
