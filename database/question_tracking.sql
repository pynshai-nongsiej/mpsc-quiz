-- Question tracking system for preventing repetition
-- This table tracks which questions have been used by each user in each category

CREATE TABLE IF NOT EXISTS user_question_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100) DEFAULT NULL,
    question_hash VARCHAR(64) NOT NULL,
    question_text TEXT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_question (user_id, category, question_hash),
    INDEX idx_user_category (user_id, category),
    INDEX idx_used_at (used_at),
    INDEX idx_question_hash (question_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table to track category completion and reset statistics
CREATE TABLE IF NOT EXISTS user_category_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    total_questions_available INT NOT NULL DEFAULT 0,
    questions_used INT NOT NULL DEFAULT 0,
    completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    last_reset_at TIMESTAMP NULL DEFAULT NULL,
    reset_count INT NOT NULL DEFAULT 0,
    average_score_before_reset DECIMAL(5,2) DEFAULT NULL,
    highest_score_before_reset DECIMAL(5,2) DEFAULT NULL,
    total_attempts_before_reset INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_category (user_id, category),
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Additional composite index for better query performance
CREATE INDEX IF NOT EXISTS idx_user_category_used ON user_question_history (user_id, category, used_at);
