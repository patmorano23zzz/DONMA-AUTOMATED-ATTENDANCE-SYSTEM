-- Run this once to add kiosk messaging support
CREATE TABLE IF NOT EXISTS kiosk_messages (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT UNSIGNED NOT NULL,
  message    VARCHAR(300) NOT NULL,
  type       ENUM('info','warning','success') NOT NULL DEFAULT 'info',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at),
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
