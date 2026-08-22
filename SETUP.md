# Setup Guide

## 1. Update the database

Open **phpMyAdmin**, select the MyDataWorld database, go to the **SQL** tab, and run everything in
[`api/schema.sql`](api/schema.sql). It's safe to run even if `users`/`sessions`/`apps`/`app_access` already exist
(from My Apps Hub or another app) — those use `CREATE TABLE IF NOT EXISTS`. This requires My Apps Hub's own
`api/schema.sql` to have already been run at least once.

The Scripture Learning app itself has also been registered in **My Apps Hub's** `api/schema.sql` (a new
`INSERT INTO apps ... ('scripture-learning', ...)` block near the end) — run that in My Apps Hub's database too, if
it hasn't been already, so the Hub knows about this app and can show its tile / hand off SSO tokens to it.

## 2. Deploy the API

1. Copy `api/config.example.php` to `api/config.php` and fill in the real `DB_NAME`, `DB_USER`, `DB_PASS` — same
   credentials as your other MyDataWorld apps.
2. Upload the whole `api/` folder via FTP/File Manager — e.g. `seniorfamily.org/scripture-api/`.

## 3. Point the site at your API

In `js/api.js`:

```js
const CONFIG = {
  API_URL: "https://seniorfamily.org/scripture-api/api.php",
};
```

Every page reads this one constant — nothing else needs to change per page.

## 4. Publish

Push this repo to GitHub — Pages serves it automatically from `main`. The Hub's `apps` row already points at
`https://dsshrek-ai.github.io/scripture-learning/` (update that row if the repo ends up named differently).

## 5. Grant yourself access

1. Sign up through **My Apps Hub** with your email, if you haven't already.
2. Open the Hub's `admin.html` and grant yourself the "Scripture Learning" app — same two-step process as any
   other private app in the Hub.
3. Repeat for anyone else who'll use this (the app is built for a small group, well under 30 users).

## Single sign-on

`apps.sso_enabled = 1` is already set for `scripture-learning` in the Hub's schema seed, so launching this app from
the Hub skips its own login screen (`?token=...` handoff). The login itself is stored in `localStorage` (not
`sessionStorage`) — same convention as most of the other apps in this family (T-Minus, PWI, Shed Inventory), since
this isn't shared-computer-sensitive data the way the Choir Admin Panel's phone numbers/notes are.
