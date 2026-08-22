# Scripture Learning

A personal scripture memorization app: add passages, read them in full, and (in later phases) practice them with
progressively fewer hints and review them on a spaced-repetition schedule.

Static HTML/CSS/JS (this repo), hosted on GitHub Pages, talking to `api/api.php` on **MyDataWorld** — the same shared
MySQL database as My Apps Hub, T-Minus, Shed Inventory, PWI Weight Tracker, and the Choir App.

## Accounts

No separate signup — this shares the same login as My Apps Hub. Sign up through the Hub first, then ask to be
granted access to "Scripture Learning". Unlike the Choir App, **every page here requires login** — scripture
progress is inherently personal, there's no anonymous/public content.

## Files

- `index.html`, `my-scriptures.html`, `add.html`, `scripture.html` — the app
- `js/api.js` — API client, auth/login gate, SSO handoff from My Apps Hub
- `style.css` — shared visual style, same design tokens as the rest of the app family
- `api/api.php` — backend (PHP + MySQL)
- `api/schema.sql` — database schema
- `api/config.example.php` — copy to `api/config.php` with real DB credentials (gitignored)

See `SETUP.md` for deployment and `ROADMAP.md` for what's built vs. planned.
