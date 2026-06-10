# Said You Would

Said You Would (saidyouwould.com) records a private video message, takes a $5 Stripe payment to seal it, and delivers the video by email one year later. Messages go to a friend (with an optional "on notice" email today, or total silence for a year) or to your future self. Written terms are stored as text only. The app does not hold money between people or enforce agreements; the $5 is the price of the seal.

## Stack

- Laravel 13 PHP API with Inertia
- Vue 3 + Vite
- MySQL
- Stripe Checkout ($5 seal, webhook + success-page verification)
- Private Cloudflare R2 video storage
- Amazon SES email delivery

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Cloudflare R2

Create a private R2 bucket and an R2 API token with object read/write permission for that bucket. Configure:

```dotenv
OYL_VIDEO_DISK=r2
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_BUCKET=one-year-later
R2_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
R2_REGION=auto
```

The browser uploads directly to a short-lived presigned R2 `PUT` URL. Playback uses a short-lived private R2 `GET` URL. R2 credentials never reach the browser.

Apply this bucket CORS policy, replacing the production origin as needed:

```json
[
  {
    "AllowedOrigins": [
      "https://saidyouwould.com",
      "https://oneyearlater.navetz.com",
      "http://localhost:5173"
    ],
    "AllowedMethods": ["PUT"],
    "AllowedHeaders": ["Content-Type"],
    "MaxAgeSeconds": 3600
  }
]
```

## Stripe

Create the API keys at <https://dashboard.stripe.com/apikeys> and a webhook endpoint at <https://dashboard.stripe.com/webhooks> pointing to `/api/stripe/webhook` with the `checkout.session.completed` event:

```dotenv
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
OYL_PRICE_CENTS=500
OYL_CURRENCY=usd
```

A challenge is created as `awaiting_payment` and only goes live (`pending`) once Stripe confirms payment, via the webhook or the success-page verification, whichever lands first (idempotent). Unpaid challenges older than 48 hours are purged (video included) by the daily delivery command.

## Amazon SES

Verify the `MAIL_FROM_ADDRESS` identity or its domain in SES and ensure the SES account can send to unverified recipients. Configure an IAM key with SES send permission:

```dotenv
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="Said You Would"
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
```

Immediate notices and future deliveries are recorded in `oyl_email_events` as `sent` or `failed`.

## Delivery Cron

Run once per day:

```cron
5 8 * * * cd /absolute/path/to/app && /usr/bin/php scripts/deliver_due.php >> storage/logs/oyl-cron.log 2>&1
```

The equivalent manual command is:

```bash
php artisan oyl:deliver-due
```

## Verification

```bash
php artisan test
npm run build
php artisan route:list --path=api
```

The test suite fakes storage and mail. It does not upload to R2 or send SES email.
