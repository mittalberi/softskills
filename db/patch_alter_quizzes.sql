ALTER TABLE quizzes
  ADD COLUMN IF NOT EXISTS available_from DATETIME NULL,
  ADD COLUMN IF NOT EXISTS available_to   DATETIME NULL,
  ADD COLUMN IF NOT EXISTS shuffle_questions TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS shuffle_options  TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS max_attempts     INT DEFAULT 1,
  ADD COLUMN IF NOT EXISTS pass_marks       DECIMAL(6,2) DEFAULT 0;
CREATE INDEX IF NOT EXISTS idx_quizzes_window ON quizzes (available_from, available_to);
