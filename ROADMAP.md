# Scripture Learning Roadmap

The original design notes (see [`scripture-app-ds.md`](scripture-app-ds.md) in this repo) described one large
"First Version" — split into three phases here instead, so each one ships as a usable app on its own rather than
needing everything built before anything works.

## Phase 1 — Foundation (done)

The core CRUD loop: log in, add a scripture, read it, mark it memorized, filter your list.

- Login via My Apps Hub SSO (shared `users`/`sessions` tables) — **every page requires login**, unlike the Choir
  App's public pages, since scripture progress is inherently personal.
- `scripture_items` table: reference, book, full text, active/available flag, memorized flag + date.
- Add Scripture, My Scriptures (filtered: All / Learning / Memorized / Available), scripture detail page with
  full-text reading, memorized/active toggles, edit, and delete.
- Home screen with basic counts (Learning / Memorized / Total) and primary actions.

**Deliberately not in Phase 1's schema**: `date_reviewed`, `text_percent`, `reference_percent`,
`require_exact_wording` (Phase 2), `next_review_date`, `learning_level` (Phase 3). None of these have code that
reads them yet — adding unused columns ahead of the feature that needs them is exactly the kind of drift that's
bitten other apps in this family before.

## Phase 2 — Practice Modes (not started)

The actual "learning" interaction loop, per the original design's assistance ladder (See Everything → See Most
Words → See Some Words → See Word Shapes/Lengths → See First Letters → See Minimal Hints → See Reference Only →
Recall Independently):

- Full-text reading (already in Phase 1) as the starting point.
- Progressive word hiding.
- Word-length blanks (show blank shapes matching word length, not the letters).
- First-letter mode.
- Recite from reference (given only the reference, recall the full text).
- `text_percent` / `reference_percent`: **last-attempt recall accuracy** — recalculated each time a practice
  attempt is scored, not a measure of how far up the hint ladder the user has climbed. (Confirmed 2026-08-22 —
  this was ambiguous in the original notes.)
- `require_exact_wording`: per-scripture flag controlling how strictly a practice attempt is graded (word-for-word
  vs. paraphrase-acceptable). Assumed meaning for the "Exact Wording" field in the original notes — flag if wrong.

## Phase 3 — Retention Engine (not started)

- Spaced-repetition scheduling. The four-button rating (Forgot It / Difficult / Got It / Easy) in the original
  notes maps closely to Anki's SM-2-derived algorithm — worth building on that rather than inventing new interval
  math from scratch.
- Suggested intervals from the original notes: same day, 1d, 3d, 7d, 14d, 30d, 60d, 90d, then longer-term
  maintenance reviews.
- Daily Review screen: scriptures whose review date is due, with reference / mastery level / days since last
  review / a review button, and the four-button rating after each attempt.
- Home screen gets its "N Reviews Due" / "Start Daily Review" primary call-to-action once this exists.

## Design Decisions Log

| Decision | Answer | Date |
|---|---|---|
| Architecture | Same as SojoMemberApp — GitHub Pages + PHP API on seniorfamily.org + shared MyDataWorld DB + My Apps Hub SSO | 2026-08-22 |
| Content source | User-submitted (each user adds their own scriptures directly — no admin curation panel needed, unlike the Choir App's Songs/Schedule) | 2026-08-22 |
| User identification | Reuse My Apps Hub's shared `users.id` directly as the numeric per-scripture user key — no separate app-specific user table | 2026-08-22 |
| Phase split | Foundation → Practice Modes → Retention Engine | 2026-08-22 |
| Text %/Reference % | Last-attempt recall accuracy, not hint-ladder progress | 2026-08-22 |
