-- Scripture Learning — schema for MyDataWorld
-- Run this in phpMyAdmin's SQL tab against the MyDataWorld database, AFTER
-- My Apps Hub's own api/schema.sql (this relies on the shared users/sessions/
-- apps/app_access tables already existing).
--
-- Table name is prefixed scripture_ so it can't collide with another app's
-- tables in this shared database.

CREATE TABLE IF NOT EXISTS scripture_items (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  reference       VARCHAR(100) NOT NULL,
  book            VARCHAR(50) NOT NULL,
  scripture_text  TEXT NOT NULL,
  -- is_active = currently being learned (shows under "Learning"). 0 means
  -- saved for later without being worked on yet (shows under "Available").
  -- Independent of is_memorized -- a memorized item can be active or not.
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  is_memorized    TINYINT(1) NOT NULL DEFAULT 0,
  date_memorized  DATE NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant yourself (and anyone else who'll use this) access to the app once
-- you've signed up through My Apps Hub -- otherwise nobody can get in.
-- INSERT INTO app_access (user_id, app_id)
-- SELECT u.id, a.id FROM users u, apps a
-- WHERE u.username = 'you@example.com' AND a.app_key = 'scripture-learning';

-- ---------- SCHEMA CHANGE: Phase 2 practice tracking ----------
-- Run once.
--
-- learning_level: 1-4, position on the practice difficulty ladder --
--   1 = progressive word hiding (~35% of words hidden)
--   2 = word-length blanks (all words hidden, shown as blanks matching length)
--   3 = first-letter mode (all words hidden except each word's first letter)
--   4 = recite from reference (no text shown at all, just the reference)
-- Advances/regresses automatically based on each attempt's text_percent --
-- see recordPracticeAttempt in api.php. Starts at 1, not 0: level 0 would
-- just be full-text reading, which isn't really "practice."
--
-- text_percent / reference_percent: last-attempt recall accuracy (0-100),
-- NULL until a first practice attempt exists. Computed client-side as
-- (words/tokens never tapped-to-reveal) / (total) x 100, since the
-- tap-to-reveal interaction (confirmed 2026-08-22) makes this fall out of
-- the interaction itself rather than needing typed-text comparison.
--
-- reference_percent specifically comes from a separate, single-difficulty
-- reverse-quiz mode (full text shown, reference hidden as word-length
-- blanks) -- a genuinely different skill from text_percent, not another
-- rung on the same ladder.
--
-- date_reviewed: date of the most recent practice attempt, either mode.
--
-- No "exact wording" column: that only makes sense if practice involves
-- comparing typed text against a canonical wording, and tap-to-reveal has
-- no typed text to compare at all.

ALTER TABLE scripture_items ADD COLUMN learning_level    TINYINT     NOT NULL DEFAULT 1 AFTER is_active;
ALTER TABLE scripture_items ADD COLUMN text_percent      TINYINT     NULL     AFTER learning_level;
ALTER TABLE scripture_items ADD COLUMN reference_percent TINYINT     NULL     AFTER text_percent;
ALTER TABLE scripture_items ADD COLUMN date_reviewed     DATE        NULL     AFTER date_memorized;

-- ---------- SCHEMA CHANGE: Phase 3 spaced repetition ----------
-- Run once.
--
-- Spaced repetition applies to MEMORIZED scriptures specifically -- it's
-- about retention after mastery, not the Phase 2 practice ladder that gets
-- you to mastery in the first place. next_review_date IS NULL means "not in
-- the retention system" (never memorized, or memorized status was later
-- manually turned back off); it gets seeded the moment is_memorized becomes
-- true (whether via Phase 2's auto-memorize or a manual toggle) and cleared
-- if is_memorized is turned back off. See updateScripture and
-- recordPracticeAttempt in api.php.
--
-- review_step: 0-7 walks the fixed interval ladder [0, 1, 3, 7, 14, 30, 60,
-- 90] days (see intervalForStep() in api.php); beyond step 7 it's
-- "maintenance mode" -- slow growth capped at 180 days, per the original
-- notes' "longer-term maintenance reviews". The four-button rating on the
-- Daily Review screen (Forgot It / Difficult / Got It / Easy) moves this
-- step down/down-a-little/up/up-more respectively. Deliberately does NOT
-- also move learning_level -- that stays governed exclusively by Text %
-- from Phase 2, so a single review attempt can't have two different rules
-- fighting over the same field. (Confirmed 2026-08-22.)

ALTER TABLE scripture_items ADD COLUMN review_step      TINYINT NOT NULL DEFAULT 0 AFTER date_reviewed;
ALTER TABLE scripture_items ADD COLUMN next_review_date DATE    NULL     AFTER review_step;
