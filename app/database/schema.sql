CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendars (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('owner', 'editor', 'viewer') NOT NULL,
    UNIQUE KEY uniq_calendar_member (calendar_id, user_id),
    CONSTRAINT fk_calendar_members_calendar FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_id INT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    created_by_user_id INT UNSIGNED NOT NULL,
    updated_by_user_id INT UNSIGNED NOT NULL,
    INDEX idx_events_calendar_range (calendar_id, starts_at, ends_at),
    CONSTRAINT fk_events_calendar FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id),
    CONSTRAINT fk_events_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, name, email) VALUES
    (1, 'Alex Owner', 'alex@example.test'),
    (2, 'Elliot Editor', 'elliot@example.test'),
    (3, 'Vera Viewer', 'vera@example.test')
ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email);

INSERT INTO calendars (id, name, timezone) VALUES
    (1, 'Team Calendar', 'UTC')
ON DUPLICATE KEY UPDATE name = VALUES(name), timezone = VALUES(timezone);

INSERT INTO calendar_members (id, calendar_id, user_id, role) VALUES
    (1, 1, 1, 'owner'),
    (2, 1, 2, 'editor'),
    (3, 1, 3, 'viewer')
ON DUPLICATE KEY UPDATE role = VALUES(role);

INSERT INTO events (id, calendar_id, title, description, starts_at, ends_at, created_by_user_id, updated_by_user_id) VALUES
    (1, 1, 'Planning session', 'Kick-off planning for the shared calendar.', '2026-08-18 09:00:00', '2026-08-18 10:00:00', 1, 1),
    (2, 1, 'Design review', 'Review the layered monolith structure.', '2026-08-19 13:00:00', '2026-08-19 14:00:00', 2, 2)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    starts_at = VALUES(starts_at),
    ends_at = VALUES(ends_at),
    updated_by_user_id = VALUES(updated_by_user_id);
