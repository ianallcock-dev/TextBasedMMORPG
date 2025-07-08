CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    cash BIGINT DEFAULT 0,
    turns INT DEFAULT 0,
    last_turn_update INT DEFAULT 0,
    thugs INT DEFAULT 0,
    entertainers INT DEFAULT 0,
    prestige INT DEFAULT 0,
    gang_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    reserved_turns INT DEFAULT 0,
    display_name VARCHAR(100),
    age_bracket VARCHAR(20),
    consent TINYINT DEFAULT 0,
    newsletter_optin TINYINT DEFAULT 0,
    last_turn_update INT DEFAULT 0,
    entertainers_last_collect INT DEFAULT 0,
    last_operation_collect_turns INT DEFAULT 0,
    last_operation_collect_time INT DEFAULT 0,
    thugs_killed_this_round       INT NOT NULL DEFAULT 0,
    entertainers_lured_this_round INT NOT NULL DEFAULT 0,
    operations INT DEFAULT 0;



    
);
CREATE TABLE gangs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    boss_user_id INT,
    vault_cash BIGINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE gang_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gang_id INT NOT NULL,
    user_id INT NOT NULL,
    rank VARCHAR(50) DEFAULT 'Soldier',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gang_id) REFERENCES gangs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE entertainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    income_per_hour INT DEFAULT 0,
    is_protected TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(50),
    level INT DEFAULT 1,
    last_collect INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE user_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_name VARCHAR(50),
    quantity INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(50),
    level INT DEFAULT 1,
    last_collect INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);


CREATE TABLE contraband_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    quantity INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE attacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attacker_id INT NOT NULL,
    defender_id INT NOT NULL,
    result TEXT,
    cash_stolen BIGINT DEFAULT 0,
    thugs_lost_attacker INT DEFAULT 0,
    thugs_lost_defender INT DEFAULT 0,
    entertainers_stolen INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attacker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (defender_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE leaderboard_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    prestige INT DEFAULT 0,
    cash BIGINT DEFAULT 0,
    thugs INT DEFAULT 0,
    entertainers INT DEFAULT 0,
    round_end_date DATETIME,
    thugs_killed INT NOT NULL DEFAULT 0,
    entertainers_lured INT NOT NULL DEFAULT 0;

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_start DATETIME,
    round_end DATETIME
);
CREATE TABLE round_events (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  round_id      INT       NOT NULL,
  occurred_at   DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actor_id      INT       NOT NULL,
  target_id     INT       NOT NULL,
  action_type   ENUM('attack','steal_cash','lure','sabotage') NOT NULL,
  amount        INT       NOT NULL,
  details       VARCHAR(255)   NULL,
  FOREIGN KEY (round_id)  REFERENCES rounds(id),
  FOREIGN KEY (actor_id)  REFERENCES users(id),
  FOREIGN KEY (target_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id`   INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `body`        TEXT     NOT NULL,
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `sent_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`)   REFERENCES `users`(`id`),
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- The gang itself
CREATE TABLE IF NOT EXISTS gangs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(50) UNIQUE NOT NULL,
  description  TEXT,
  leader_id    INT NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (leader_id) REFERENCES users(id)
);

-- Who belongs where and in what role
CREATE TABLE IF NOT EXISTS gang_members (
  gang_id      INT NOT NULL,
  user_id      INT NOT NULL,
  role         ENUM('leader','deputy','member') NOT NULL,
  description TEXT NULL AFTER name;
  joined_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (gang_id, user_id),
  FOREIGN KEY (gang_id) REFERENCES gangs(id)  ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE
);



-- Outgoing invites: leader/deputy invite a user
CREATE TABLE IF NOT EXISTS gang_invites (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  gang_id      INT NOT NULL,
  user_id      INT NOT NULL,          -- the person being invited
  invited_by   INT NOT NULL,          -- who sent the invite
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (gang_id)    REFERENCES gangs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
);

-- The gang’s private message board
CREATE TABLE IF NOT EXISTS gang_posts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  gang_id      INT NOT NULL,
  user_id      INT NOT NULL,
  content      TEXT    NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gang_id) REFERENCES gangs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE INDEX idx_users_gang_id ON users (gang_id);
CREATE INDEX idx_gang_members_user ON gang_members (user_id);
CREATE INDEX idx_entertainers_user ON entertainers (user_id);
CREATE INDEX idx_workshops_user ON workshops (user_id);
CREATE INDEX idx_attacks_attacker ON attacks (attacker_id);
CREATE INDEX idx_attacks_defender ON attacks (defender_id);
