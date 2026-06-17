-- Create database
CREATE DATABASE IF NOT EXISTS lectra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lectra;

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_departments_name (name)
);

-- Faculties table
CREATE TABLE faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_faculties_name (name)
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'lecturer', 'student') NOT NULL,
    department_id INT,
    device_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_department (department_id)
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    lecturer_id INT,
    department_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    
    INDEX idx_courses_code (code),
    INDEX idx_courses_lecturer (lecturer_id),
    INDEX idx_courses_department (department_id)
);

-- Timetable table
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(100) NOT NULL,
    week INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    INDEX idx_timetable_course (course_id),
    INDEX idx_timetable_day (day),
    INDEX idx_timetable_week (week),
    INDEX idx_timetable_venue (venue)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'alert') NOT NULL DEFAULT 'info',
    target_role ENUM('admin', 'lecturer', 'student', 'all') NOT NULL DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    
    INDEX idx_notifications_type (type),
    INDEX idx_notifications_target_role (target_role),
    INDEX idx_notifications_created (created_at)
);

-- Announcements table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_emergency BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_announcements_emergency (is_emergency),
    INDEX idx_announcements_created (created_at)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    timetable_id INT NOT NULL,
    confirmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    
    
    UNIQUE KEY unique_attendance (student_id, timetable_id),
    INDEX idx_attendance_student (student_id),
    INDEX idx_attendance_timetable (timetable_id),
    INDEX idx_attendance_confirmed (confirmed_at)
);

-- Seed data: Departments
INSERT INTO departments (name) VALUES 
('Computer Science'),
('Electrical Engineering'),
('Accountancy');

-- Seed data: Faculties
INSERT INTO faculties (name) VALUES 
('Faculty of Science'),
('Faculty of Engineering'),
('Faculty of Management Science');

-- Seed data: Users (password_hash is bcrypt of 'password123')
INSERT INTO users (name, email, password_hash, role, department_id) VALUES 
('Admin User', 'admin@lectra.edu', '$2y$10$M6usJqP1FXxU/nKG4Yx.9uOHT46/f53qQPpZyPZFY4kSJvVgcvZwa', 'admin', 1),
('Dr. Jane Smith', 'lecturer@lectra.edu', '$2y$10$M6usJqP1FXxU/nKG4Yx.9uOHT46/f53qQPpZyPZFY4kSJvVgcvZwa', 'lecturer', 1),
('John ayoola', 'student@lectra.edu', '$2y$10$M6usJqP1FXxU/nKG4Yx.9uOHT46/f53qQPpZyPZFY4kSJvVgcvZwa', 'student', 1);




-- Seed data: Courses
INSERT INTO courses (code, title, lecturer_id, department_id) VALUES 
('CS101', 'Introduction to Programming', 2, 1),
('CS201', 'Data Structures and Algorithms', 2, 1),
('CS301', 'Database Systems', 2, 1),
('EE101', 'Circuit Analysis', 2, 2),
('EE201', 'Digital Electronics', 2, 2),
('EE301', 'Power Systems', 2, 2),
('ACC331', 'Public Sector Accounting', 2, 3),
('ACC203', 'Corporate Governance', 2, 3),
('ACC308', 'Public Sector Accounting & Reporting', 2, 3),
('CS401', 'Software Engineering', 2, 1);

-- Seed data: Timetable (2 weeks)
-- Week 1
INSERT INTO timetable (course_id, day, start_time, end_time, venue, week) VALUES 
(1, 'Monday', '09:00:00', '11:00:00', 'Room 101', 1),
(2, 'Monday', '14:00:00', '16:00:00', 'Room 201', 1),
(3, 'Tuesday', '10:00:00', '12:00:00', 'Lab A', 1),
(4, 'Tuesday', '13:00:00', '15:00:00', 'Room 301', 1),
(5, 'Wednesday', '09:00:00', '11:00:00', 'Room 202', 1),
(6, 'Wednesday', '14:00:00', '16:00:00', 'Lab B', 1),
(7, 'Thursday', '11:00:00', '13:00:00', 'Room 101', 1),
(8, 'Thursday', '15:00:00', '17:00:00', 'Room 203', 1),
(9, 'Friday', '10:00:00', '12:00:00', 'Room 102', 1),
(10, 'Friday', '13:00:00', '15:00:00', 'Lab A', 1);

-- Week 2
INSERT INTO timetable (course_id, day, start_time, end_time, venue, week) VALUES 
(1, 'Monday', '09:00:00', '11:00:00', 'Room 101', 2),
(2, 'Monday', '14:00:00', '16:00:00', 'Room 201', 2),
(3, 'Tuesday', '10:00:00', '12:00:00', 'Lab A', 2),
(4, 'Tuesday', '13:00:00', '15:00:00', 'Room 301', 2),
(5, 'Wednesday', '09:00:00', '11:00:00', 'Room 202', 2),
(6, 'Wednesday', '14:00:00', '16:00:00', 'Lab B', 2),
(7, 'Thursday', '11:00:00', '13:00:00', 'Room 101', 2),
(8, 'Thursday', '15:00:00', '17:00:00', 'Room 203', 2),
(9, 'Friday', '10:00:00', '12:00:00', 'Room 102', 2),
(10, 'Friday', '13:00:00', '15:00:00', 'Lab A', 2);

-- Seed data: Notifications
INSERT INTO notifications (title, message, type, target_role) VALUES 
('System Update', 'The system will undergo maintenance this weekend', 'info', 'all'),
('Important Notice', 'Final exam schedule has been posted', 'warning', 'student'),
('Emergency Alert', 'Campus closed due to weather conditions', 'alert', 'all');

-- Seed data: Announcements
INSERT INTO announcements (title, body, is_emergency) VALUES 
('Welcome to Lecktra', 'Welcome to the new academic year at our institution', FALSE),
('Exam Registration', 'Exam registration closes this Friday', FALSE),
('Campus Closure', 'Due to severe weather, campus will be closed tomorrow', TRUE);

-- Seed data: Attendance
INSERT INTO attendance (student_id, timetable_id, confirmed_at) VALUES 
(3, 1, NOW()),
(3, 2, NOW()),
(3, 3, NOW()),
(3, 4, NOW()),
(3, 5, NOW()),
(3, 6, NOW()),
(3, 7, NOW()),
(3, 8, NOW()),
(3, 9, NOW()),
(3, 10, NOW());

SELECT 'Database setup complete!' as message;