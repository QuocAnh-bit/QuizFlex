-- Chạy file này chỉ khi bạn không muốn dùng php artisan migrate.
-- Nếu dùng migrate thì KHÔNG cần chạy thủ công.

ALTER TABLE quizzes
  ADD COLUMN IF NOT EXISTS tag VARCHAR(100) NULL AFTER category,
  ADD COLUMN IF NOT EXISTS room_code VARCHAR(32) NULL AFTER is_public,
  ADD COLUMN IF NOT EXISTS cover TEXT NULL AFTER time_limit_seconds,
  ADD COLUMN IF NOT EXISTS icon VARCHAR(32) NULL AFTER cover,
  ADD COLUMN IF NOT EXISTS badge VARCHAR(32) NULL AFTER icon;

ALTER TABLE quizzes MODIFY cover TEXT NULL;

CREATE INDEX IF NOT EXISTS quizzes_room_code_index ON quizzes (room_code);
