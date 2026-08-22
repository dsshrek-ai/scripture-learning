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

-- ---------- PHASE 2 (not yet needed) ----------
-- Practice-attempt tracking will add: date_reviewed DATE NULL,
-- text_percent TINYINT NULL, reference_percent TINYINT NULL,
-- require_exact_wording TINYINT(1) NOT NULL DEFAULT 0.
-- Deliberately not added yet -- no code reads them until Phase 2 exists,
-- and an unused column is exactly the kind of drift worth avoiding.

-- ---------- PHASE 3 (not yet needed) ----------
-- Spaced repetition will add: next_review_date DATE NULL,
-- learning_level TINYINT NOT NULL DEFAULT 0. Same reasoning as above.
