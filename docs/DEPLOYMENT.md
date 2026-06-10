# Server deployment notes

## Runtime permissions

Laravel must be able to write `storage/` and `bootstrap/cache/`. On this ISPConfig host, the Said You Would private runtime directory is group-owned by `client1` and setgid so PHP-FPM (`web26`) can create private upload and video files:

```bash
chgrp -R client1 storage/app/private/one-year-later
chmod 2770 storage/app/private/one-year-later storage/app/private/one-year-later/uploads
```

Videos are never placed under `public/`.

## Upload limits

The browser automatically uploads videos in authenticated 700 KB chunks. This allows the 100 MB application limit to work even when Nginx retains its default 1 MB request-body limit. The server assembles the chunks in private storage, verifies the final byte count and MIME type, then moves the file through the configured `VideoStorage` implementation.

Direct, non-browser API clients that send the whole video in one request also need matching proxy and PHP limits. The repository includes `.user.ini` files for PHP. For Nginx, add this inside the site's `server` block and reload after `nginx -t` succeeds:

```nginx
client_max_body_size 105m;
```

## Frontend build

Vite 8 requires Node.js 20.19+ or 22.12+. The current host has Node.js 20.18.1; builds complete, but upgrading removes the compatibility warning.

## Cron

Use either the standalone wrapper or Laravel scheduler documented in the main README. Test the command against a non-production database before the first production run because a due challenge triggers email and changes its status to `delivered`.
