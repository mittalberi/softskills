-- Core tables
CREATE TABLE roles (
  id TINYINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(20) UNIQUE NOT NULL
);

CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role_id TINYINT NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE courses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  short_desc VARCHAR(300),
  description MEDIUMTEXT,
  is_published TINYINT(1) DEFAULT 0,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE modules (
  id INT PRIMARY KEY AUTO_INCREMENT,
  course_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE lessons (
  id INT PRIMARY KEY AUTO_INCREMENT,
  module_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  content MEDIUMTEXT,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

CREATE TABLE quizzes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  course_id INT,
  title VARCHAR(200) NOT NULL,
  duration_minutes INT DEFAULT 30,
  is_mock TINYINT(1) DEFAULT 0,
  is_published TINYINT(1) DEFAULT 0,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE questions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_tag ENUM('infosys','tcs','wipro','generic') DEFAULT 'generic',
  topic VARCHAR(100),
  question_type VARCHAR(20) NOT NULL,
  question_text MEDIUMTEXT NOT NULL,
  option_a TEXT, option_b TEXT, option_c TEXT, option_d TEXT,
  correct_answer VARCHAR(50) NOT NULL,
  explanation MEDIUMTEXT,
  difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
  created_by INT,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE quiz_questions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  quiz_id INT NOT NULL,
  question_id INT NOT NULL,
  marks DECIMAL(5,2) DEFAULT 1.00,
  neg_marks DECIMAL(5,2) DEFAULT 0.00,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE quiz_attempts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  quiz_id INT NOT NULL,
  user_id INT NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at DATETIME NULL,
  score DECIMAL(6,2) DEFAULT 0.00,
  total_marks DECIMAL(6,2) DEFAULT 0.00,
  status ENUM('in_progress','submitted','abandoned') DEFAULT 'in_progress',
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE quiz_attempt_answers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  attempt_id BIGINT NOT NULL,
  question_id INT NOT NULL,
  chosen_answer VARCHAR(100),
  is_correct TINYINT(1) DEFAULT 0,
  marks_awarded DECIMAL(5,2) DEFAULT 0.00,
  FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id)
);

CREATE TABLE leaderboard (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  total_score DECIMAL(10,2) DEFAULT 0.00,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
