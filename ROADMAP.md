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
`learning_level` (Phase 2), `next_review_date` (Phase 3). None of these had code that read them yet — adding unused
columns ahead of the feature that needs them is exactly the kind of drift that's bitten other apps in this family
before.

## Phase 2 — Practice Modes (done)

The original design's 8-step assistance ladder (See Everything → ... → Recall Independently) was a narrative
describing *why* the ladder gets harder, not 8 literal implementation levels — it collapses onto the four concrete
modes actually named in the feature list:

- **Level 1 — Progressive word hiding**: ~35% of words randomly hidden per attempt (re-randomized every practice),
  shown as a plain hidden marker with no shape hint.
- **Level 2 — Word-length blanks**: every word hidden, shown as underscores matching its letter/digit count;
  punctuation stays visible so sentence structure is still readable.
- **Level 3 — First-letter mode**: every word hidden except its first letter/digit.
- **Level 4 — Recite from reference**: every word hidden with no shape hint at all — only the reference itself is
  given as the prompt.
- **Interaction model (confirmed 2026-08-22)**: tap-to-reveal, not typing. Hidden words are tappable; tapping
  reveals just that word. `text_percent` = (words never tapped) / (total words) × 100 — falls directly out of the
  interaction, satisfying the "minimal typing" mobile-first requirement.
- **Auto-advance (confirmed 2026-08-22)**: `learning_level` moves up one (max 4) after an attempt scoring ≥85%,
  down one (min 1) after an attempt scoring <50%, otherwise stays put. Reaching level 4 at ≥85% auto-sets
  `is_memorized = 1` — that's what "independent recall" in the original design principle actually means. Manually
  un-marking Memorized is still possible on the scripture detail page; the milestone just doesn't require the user
  to notice and flip it themselves.
- **`reference_percent` (confirmed 2026-08-22)**: a genuinely separate skill from `text_percent`, not another rung
  on the same ladder — a single-difficulty reverse quiz (full scripture text shown, the reference itself hidden as
  word-length blanks, same tap-to-reveal scoring). Doesn't affect `learning_level`.
- **Dropped from the original notes**: the "Exact Wording" field. That only makes sense if practice involves
  comparing typed text against a canonical wording, and tap-to-reveal has no typed text to compare at all — so
  there was nothing left for it to control once the interaction model was settled.

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
| Practice interaction model | Tap-to-reveal hidden words, not typed/checked text | 2026-08-22 |
| Reference % meaning | Reverse quiz (text shown, reference hidden) — a separate skill from Text % | 2026-08-22 |
| Level progression | Auto-advance based on attempt score (≥85% up, <50% down) | 2026-08-22 |
| "Exact Wording" field | Dropped — no typed text exists to grade against it under tap-to-reveal | 2026-08-22 |
