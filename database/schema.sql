-- MPSC Quiz Portal Database Schema
-- Created for user authentication, quiz tracking, and performance analytics

-- Users table for authentication and profile management
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    profile_picture VARCHAR(255) DEFAULT NULL
);

-- Quiz attempts table to track all quiz sessions
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_type VARCHAR(50) NOT NULL, -- 'mpsc_lda', 'dsc_lda', 'mpsc_typist', 'general_mock'
    quiz_title VARCHAR(200) NOT NULL,
    total_questions INT NOT NULL,
    correct_answers INT NOT NULL,
    score INT NOT NULL,
    max_score INT NOT NULL,
    accuracy DECIMAL(5,2) NOT NULL, -- Percentage accuracy
    time_taken INT NOT NULL, -- Time in seconds
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_quiz (user_id, quiz_type),
    INDEX idx_completed_at (completed_at)
);

-- Quiz question responses for detailed analysis
CREATE TABLE IF NOT EXISTS quiz_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_number INT NOT NULL,
    question_text TEXT NOT NULL,
    user_answer VARCHAR(10),
    correct_answer VARCHAR(10) NOT NULL,
    is_correct BOOLEAN NOT NULL,
    category VARCHAR(100),
    subcategory VARCHAR(100),
    time_spent INT DEFAULT 0, -- Time spent on this question in seconds
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    INDEX idx_attempt_question (attempt_id, question_number),
    INDEX idx_category (category, subcategory)
);

-- User statistics for performance tracking
CREATE TABLE IF NOT EXISTS user_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_quizzes INT DEFAULT 0,
    total_questions_answered INT DEFAULT 0,
    total_correct_answers INT DEFAULT 0,
    average_accuracy DECIMAL(5,2) DEFAULT 0.00,
    total_time_spent INT DEFAULT 0, -- Total time in seconds
    best_score INT DEFAULT 0,
    best_accuracy DECIMAL(5,2) DEFAULT 0.00,
    current_streak INT DEFAULT 0, -- Current streak of quizzes taken
    longest_streak INT DEFAULT 0,
    last_quiz_date DATE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_stats (user_id)
);

-- Category performance for detailed analytics
CREATE TABLE IF NOT EXISTS category_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100),
    total_questions INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    accuracy DECIMAL(5,2) DEFAULT 0.00,
    average_time DECIMAL(8,2) DEFAULT 0.00, -- Average time per question in seconds
    last_attempted TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, category, subcategory),
    INDEX idx_category_performance (category, subcategory)
);

-- Daily performance tracking for charts
CREATE TABLE IF NOT EXISTS daily_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    quizzes_taken INT DEFAULT 0,
    questions_answered INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    accuracy DECIMAL(5,2) DEFAULT 0.00,
    time_spent INT DEFAULT 0, -- Time in seconds
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date)
);



-- User sessions for login management
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_session (user_id, is_active),
    INDEX idx_expires (expires_at)
);

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, email, password_hash, full_name, is_active) 
VALUES ('admin', 'admin@mpscquiz.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', TRUE);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_quiz_attempts_user_date ON quiz_attempts(user_id, completed_at);
CREATE INDEX idx_quiz_attempts_type_score ON quiz_attempts(quiz_type, score DESC);
CREATE INDEX idx_user_statistics_accuracy ON user_statistics(average_accuracy DESC);
CREATE INDEX idx_daily_performance_user_date ON daily_performance(user_id, date DESC);

-- Views for common queries

CREATE OR REPLACE VIEW recent_quiz_performance AS
SELECT 
    qa.id,
    qa.user_id,
    u.username,
    qa.quiz_type,
    qa.quiz_title,
    qa.score,
    qa.max_score,
    qa.accuracy,
    qa.time_taken,
    qa.completed_at,
    RANK() OVER (PARTITION BY qa.quiz_type ORDER BY qa.score DESC, qa.accuracy DESC) as type_rank
FROM quiz_attempts qa
JOIN users u ON qa.user_id = u.id
WHERE qa.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY qa.completed_at DESC;