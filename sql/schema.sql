

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    wins INT NOT NULL DEFAULT 0,
    games_played INT NOT NULL DEFAULT 0,
    best_level INT NOT NULL DEFAULT 1,
    title VARCHAR(100) NOT NULL DEFAULT 'New Player',
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    last_seen TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    difficulty INT NOT NULL,
    level_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    level_id INT NOT NULL,
    time_ms INT NOT NULL,
    moves INT NOT NULL,
    mode VARCHAR(20) NOT NULL DEFAULT 'custom',
    won TINYINT(1) NOT NULL DEFAULT 0,
    keys_collected INT NOT NULL DEFAULT 0,
    points_earned INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_unlock (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- For deployments onto an existing DB, run:
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL DEFAULT NULL;

DELETE FROM user_achievements;
DELETE FROM achievements;
DELETE FROM scores;
DELETE FROM levels;
ALTER TABLE achievements AUTO_INCREMENT = 1;

INSERT INTO achievements (code, name, description) VALUES
('FIRST_ESCAPE', 'First Escape', 'Complete your first successful run.'),
('TEN_GAMES', 'Getting Started', 'Play 10 games in total.'),
('FIVE_WINS', 'Consistent Runner', 'Win 5 games.'),
('LEVEL_5', 'Deep Runner', 'Beat Level 5 or higher.'),
('POINTS_1000', 'Beginner Explorer', 'Reach 1000 total points.'),
('POINTS_2500', 'Maze Hunter', 'Reach 2500 total points.'),
('POINTS_7000', 'Escape Master', 'Reach 7000 total points.'),
('POINTS_15000', 'Shadow Sprinter', 'Reach 15000 total points.'),
('POINTS_20000', 'Maze Legend', 'Reach 20000 total points.'),
('KEY_COLLECTOR', 'Key Collector', 'Collect 25 keys across all runs.'),
('SPEED_40', 'Rapid Exit', 'Finish any level in under 40 seconds.'),
('HARD_CLEAR', 'No More Training Wheels', 'Beat Level 8.');

INSERT INTO levels (name, difficulty, level_json) VALUES
('Level 1', 1, '{
  "w": 16,
  "h": 12,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 14, "y": 1},
  "enemies": [{"x": 14, "y": 10}],
  "keys": [{"x": 7, "y": 5}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,0,1,1,1,0,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,1,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,0,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,0,0,1,0,0,0,1],
    [1,0,1,0,1,0,1,1,1,1,0,1,0,1,0,1],
    [1,0,1,0,0,0,0,0,0,1,0,0,0,1,0,1],
    [1,0,1,1,1,1,1,1,0,1,1,1,0,1,0,1],
    [1,0,0,0,0,0,0,1,0,0,0,0,0,1,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}'),
('Level 2', 2, '{
  "w": 18,
  "h": 13,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 16, "y": 1},
  "enemies": [{"x": 16, "y": 11}],
  "keys": [{"x": 8, "y": 7}, {"x": 3, "y": 11}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,0,1,1,1,1,1,0,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,0,0,1,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,1,1,0,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,1,0,0,0,0,1,0,0,0,1],
    [1,0,1,0,1,0,1,0,1,1,1,1,0,1,0,1,0,1],
    [1,0,1,0,0,0,1,0,0,0,0,1,0,0,0,1,0,1],
    [1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,1,0,0,0,1,0,0,0,1],
    [1,1,1,0,1,1,1,1,0,1,1,1,0,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}'),
('Level 3', 3, '{
  "w": 18,
  "h": 13,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 16, "y": 1},
  "enemies": [{"x": 16, "y": 11}, {"x": 9, "y": 11}],
  "keys": [{"x": 7, "y": 7}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,0,1,1,1,1,1,0,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,0,0,1,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,1,1,0,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,1,0,0,0,0,1,0,0,0,1],
    [1,0,1,0,1,0,1,0,1,1,1,1,0,1,0,1,0,1],
    [1,0,1,0,0,0,1,0,0,0,0,1,0,0,0,1,0,1],
    [1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,1,0,0,0,1,0,0,0,1],
    [1,1,1,0,1,1,1,1,0,1,1,1,0,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}'),
('Level 4', 4, '{
  "w": 20,
  "h": 14,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 18, "y": 1},
  "enemies": [{"x": 18, "y": 12}, {"x": 10, "y": 12}],
  "keys": [{"x": 5, "y": 9}, {"x": 14, "y": 9}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,1,0,1,1,1,1,1,1,0,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,1,1,1,1,0,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,0,1,0,0,0,0,1,0,0,0,1],
    [1,1,1,0,1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1],
    [1,0,0,0,1,0,1,0,0,0,0,0,0,1,0,1,0,0,0,1],
    [1,0,1,1,1,0,1,0,1,1,1,1,0,1,0,1,0,1,1,1],
    [1,0,0,0,0,0,1,0,0,0,0,1,0,0,0,1,0,0,0,1],
    [1,0,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}'),
('Level 5', 5, '{
  "w": 20,
  "h": 14,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 18, "y": 1},
  "enemies": [{"x": 18, "y": 12}, {"x": 10, "y": 12}, {"x": 1, "y": 12}],
  "keys": [{"x": 4, "y": 9}, {"x": 10, "y": 7}, {"x": 16, "y": 9}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,1,0,1,1,1,1,1,1,0,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,1,1,1,1,0,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,0,1,0,0,0,0,1,0,0,0,1],
    [1,1,1,0,1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1],
    [1,0,0,0,1,0,1,0,0,0,0,0,0,1,0,1,0,0,0,1],
    [1,0,1,1,1,0,1,0,1,1,1,1,0,1,0,1,0,1,1,1],
    [1,0,0,0,0,0,1,0,0,0,0,1,0,0,0,1,0,0,0,1],
    [1,0,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}'),
('Level 6', 6, '{
  "w": 22,
  "h": 14,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 20, "y": 1},
  "enemies": [{"x": 20, "y": 12}, {"x": 11, "y": 12}],
  "keys": [{"x": 5, "y": 7}, {"x": 16, "y": 7}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,1,1,0,1,1,1,1,1,0,1,1,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,1,1,1,1,0,1,1,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,0,1,0,0,0,0,0,0,1,0,0,0,1],
    [1,1,1,0,1,0,1,1,1,0,1,1,1,1,1,1,0,1,1,1,0,1],
    [1,0,0,0,1,0,1,0,0,0,0,0,0,1,0,0,0,1,0,0,0,1],
    [1,0,1,1,1,0,1,0,1,1,1,1,0,1,0,1,1,1,0,1,1,1],
    [1,0,0,0,0,0,1,0,0,0,0,1,0,0,0,1,0,0,0,0,0,1],
    [1,0,1,1,1,1,1,1,1,1,0,1,1,1,0,1,1,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,0,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}'),
('Level 7', 7, '{
  "w": 22,
  "h": 14,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 20, "y": 1},
  "enemies": [{"x": 20, "y": 12}, {"x": 11, "y": 12}, {"x": 1, "y": 12}],
  "keys": [{"x": 7, "y": 9}, {"x": 14, "y": 9}, {"x": 10, "y": 5}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,1,1,0,1,1,1,1,1,0,1,1,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,1,1,1,1,0,1,1,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,0,1,0,0,0,0,0,0,1,0,0,0,1],
    [1,1,1,0,1,0,1,1,1,0,1,1,1,1,1,1,0,1,1,1,0,1],
    [1,0,0,0,1,0,1,0,0,0,0,0,0,1,0,0,0,1,0,0,0,1],
    [1,0,1,1,1,0,1,0,1,1,1,1,0,1,0,1,1,1,0,1,1,1],
    [1,0,0,0,0,0,1,0,0,0,0,1,0,0,0,1,0,0,0,0,0,1],
    [1,0,1,1,1,1,1,1,1,1,0,1,1,1,0,1,1,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,0,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}'),
('Level 8', 8, '{
  "w": 24,
  "h": 15,
  "player": {"x": 1, "y": 1},
  "exit": {"x": 22, "y": 1},
  "enemies": [{"x": 22, "y": 13}, {"x": 12, "y": 13}, {"x": 1, "y": 13}, {"x": 18, "y": 7}],
  "keys": [{"x": 5, "y": 9}, {"x": 11, "y": 7}, {"x": 18, "y": 9}],
  "walls": [
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,1,1,0,1,1,1,1,1,0,1,1,1,1,1,1,1,1,0,1],
    [1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,1,0,1],
    [1,0,1,0,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,0,1,0,1],
    [1,0,0,0,1,0,0,0,0,0,1,0,0,0,0,0,0,0,0,1,0,0,0,1],
    [1,1,1,0,1,0,1,1,1,0,1,1,1,1,1,1,1,1,0,1,1,1,0,1],
    [1,0,0,0,1,0,1,0,0,0,0,0,0,1,0,0,0,1,0,1,0,0,0,1],
    [1,0,1,1,1,0,1,0,1,1,1,1,0,1,0,1,0,1,0,1,0,1,1,1],
    [1,0,0,0,0,0,1,0,0,0,0,1,0,0,0,1,0,0,0,1,0,0,0,1],
    [1,0,1,1,1,1,1,1,1,1,0,1,1,1,0,1,1,1,1,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,0,0,0,0,1],
    [1,0,1,1,1,1,1,1,0,1,1,1,0,1,1,1,1,1,1,1,1,1,0,1],
    [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
    [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
  ]
}');
