-- ==========================================
-- PMS DEMO SEED DATA
-- All accounts use password: password123
-- ==========================================
--
-- Sections 1-6 are the original handcrafted seed (real faculty, classmates,
-- USNs and project teams of 5th Semester CS, A & B Section), unchanged except
-- that the classroom row now carries a schedule and team-size limits.
-- Section 7 layers the Saturday weekly guide meetings, guide attendance logs, immutable attendance changes, actionable directives / guide instructions, CIE marks sheet, classroom milestones, team marketplace, and notifications on top of those same real teams.
--
-- Run schema.sql first, then this file. Re-running it resets all demo data.
-- ==========================================

USE pms;

-- 0. Reset (keeps the table structure from schema.sql, clears the data)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE marks_changes;
TRUNCATE TABLE student_marks;
TRUNCATE TABLE project_marks;
TRUNCATE TABLE guide_instructions;
TRUNCATE TABLE attendance_changes;
TRUNCATE TABLE meeting_attendance;
TRUNCATE TABLE weekly_meetings;
TRUNCATE TABLE weekly_attendance;
TRUNCATE TABLE weekly_reviews;
TRUNCATE TABLE weekly_submission_files;
TRUNCATE TABLE weekly_submissions;
TRUNCATE TABLE notifications;
TRUNCATE TABLE classroom_phases;
TRUNCATE TABLE classroom_milestones;
TRUNCATE TABLE project_members;
TRUNCATE TABLE projects;
TRUNCATE TABLE classroom_members;
TRUNCATE TABLE classrooms;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (id, username, password, role) VALUES (1, 'Coordinator Admin', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (2, 'Roopa', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (3, 'Nanda Kumar', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (4, 'NaghaLakshmi', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (5, 'Sharanya', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (6, 'SoniyaKomal', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (7, 'Soniya Komal', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (8, 'Meghashree', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (9, 'Arudra A', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (10, 'Bhagyashree wakde', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (11, 'Latha P H', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (12, 'Deepti N', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (13, 'ProfSharanya', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (14, 'Nagalakshmi', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (15, 'CHAITHRA AB', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (16, 'KEERTHANA', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (17, 'ANKITHA V', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (18, 'ARCHANA NAVI', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (19, 'LOKESH N KULER', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (20, 'MANJUNATH R', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (21, 'MANYA B Y', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (22, 'BHARGAVI GOWDA R', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (23, 'ADHITYA G N', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (24, 'AKSHAYA B L', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (25, 'CHINMAYI', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (26, 'KUSHI S R GOWDA', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (27, 'ALZIYA KULUM I A', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (28, 'HUMA KOUNAIN SAIFI', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (29, 'NAMIRA FATHIMA', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (30, 'MEHER KHANUM', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (31, 'ABHISHEK A ULLI', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (32, 'AMULYA M', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (33, 'ERIN ELSA PHLIP', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (34, 'JEEVAN', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (35, 'GAYANA B', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (36, 'G SHWETHA', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (37, 'FAIZA RIDA', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (38, 'BHAGYASHREE', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (39, 'K DHANALAKSHMI', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (40, 'GAGANASHRI T.A', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (41, 'GAYITHRI S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (42, 'GEETHA T', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (43, 'HEMALATHA DH', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (44, 'MAHALAXMI', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (45, 'MOHANAKSHI CM', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (46, 'NAMRATHA P', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (47, 'MRINALIK RAJ', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (48, 'ARJUN VIKAS NAIK', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (49, 'MOHAMMED HUZAIFA', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (50, 'YASIR RAJA', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (51, 'A Abdul Younus', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (52, 'Mohammed talaha', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (53, 'Mohammed sami', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (54, 'Henry DawsonG', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (55, 'Hemanth kumar KR', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (56, 'Mohammed Maaz', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (57, 'Darshan', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (58, 'KISHAN', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (59, 'Koresh', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (60, 'Laxman', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (61, 'MALLIK I L', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (62, 'Khaja mainoddin', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (63, 'Ayan', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (64, 'ANRAJ', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (65, 'Twayib', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (66, 'siddharth', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (67, 'Suhass', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (68, 'Sameer', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (69, 'Saquib', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (70, 'Raiyaan', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (71, 'Varshini G', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (72, 'Shivani Kumari', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (73, 'Sushmita C M', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (74, 'shodhan R', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (75, 'Prajwal', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (76, 'sudiksha D', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (77, 'sneha Sanjeev Mayannavar', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (78, 'vinay kumar G S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (79, 'Pavan kalli', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (80, 'prashanth K S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (81, 'Shreyas', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (82, 'Saima firdouse ghouri', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (83, 'shravya babu', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (84, 'wajiha wasfis', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (85, 'Usha K', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (86, 'shrusti S R', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (87, 'Shreedevi I S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (88, 'preeti c', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (89, 'Navyashree R', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (90, 'Sindhu M G', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (91, 'Siddartha N', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (92, 'abdul Aziz', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (93, 'Syed zaid Ahmed', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (94, 'Rosde ali khan', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (95, 'Rushan', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (96, 'Ranjith kumar', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (97, 'Nithin gowda', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (98, 'Vinutha H K', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (99, 'Vinaya kumar', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (100, 'Ramya', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (101, 'Rakshitha', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (102, 'Ruchitha', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (103, 'Pravalika', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (104, 'Rohith S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (105, 'Y D Meghana', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (106, 'Sandya T S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (107, 'soni bai S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (108, 'Rishi', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (109, 'Puneeth', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (110, 'Vaishnavi', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (111, 'Saniya', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (112, 'swapna Ashok Hullur', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (113, 'Saara Rafi', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (114, 'Syed umme haani', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (115, 'Madiha aiman', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (116, 'Ankita k S', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (117, 'S pradeep', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (118, 'Praveen M', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);
INSERT INTO users (id, username, password, role) VALUES (119, 'Praveen R', '$2y$10$9ms1LzhGBwa99/gyo3oNCu3a3bMxcVd7hJl8UG756xvobdTN4yiJq', NULL);

-- 4. Create Classroom
INSERT INTO classrooms (id, name, created_by, invite_code, requires_usn, min_team_size, max_team_size, start_date, end_date) VALUES (1, '5th Semester CS (A & B Section)', 1, 'VTU26X', 1, 3, 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 59 DAY));
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 1, 'Admin', NULL);

-- 5. Add Guides and Students to Classroom
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 2, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 3, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 4, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 5, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 6, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 7, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 8, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 9, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 10, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 11, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 12, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 13, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 14, 'Admin', NULL);
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 15, 'Team Member', '1RG24CS015');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 16, 'Team Member', '1RG24CS034');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 17, 'Team Member', '1RG24CS009');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 18, 'Team Member', '1RG24CS011');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 19, 'Team Member', '1RG24CS039');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 20, 'Team Member', '1RG24CS043');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 21, 'Team Member', '1RG24CS044');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 22, 'Team Member', '1RG24CS014');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 23, 'Team Member', '1RG24CS003');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 24, 'Team Member', '1RG24CS006');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 25, 'Team Member', '1RG24CS017');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 26, 'Team Member', '1RG24CS037');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 27, 'Team Member', '1RG24CS007');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 28, 'Team Member', '1RG24CS030');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 29, 'Team Member', '1RG24CS056');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 30, 'Team Member', '1RG24CS045');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 31, 'Team Member', '1RG24CS002');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 32, 'Team Member', '1RG24CS008');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 33, 'Team Member', '1RG24CS020');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 34, 'Team Member', '1RG24CS032');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 35, 'Team Member', '1RG24CS024');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 36, 'Team Member', '1RG24CS022');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 37, 'Team Member', '1RG24CS021');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 38, 'Team Member', '1RG24CS013');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 39, 'Team Member', '1RG24CS033');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 40, 'Team Member', '1RG24CS023');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 41, 'Team Member', '1RG24CS025');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 42, 'Team Member', '1RG24CS026');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 43, 'Team Member', '1RG24CS027');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 44, 'Team Member', '1RG24CS042');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 45, 'Team Member', '1RG24CS053');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 46, 'Team Member', '1RG24CS057');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 47, 'Team Member', '1RG24CS054');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 48, 'Team Member', '1RG24CS012');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 49, 'Team Member', '1RG24CS048');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 50, 'Team Member', '1RG24CS040');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 51, 'Team Member', '1RG24CS001');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 52, 'Team Member', '1RG24CS052');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 53, 'Team Member', '1RG24CS050');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 54, 'Team Member', '1RG24CS029');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 55, 'Team Member', '1RG24CS028');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 56, 'Team Member', '1RG24CS049');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 57, 'Team Member', '1RG24CS018');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 58, 'Team Member', '1RG24CS035');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 59, 'Team Member', '1RG24CS036');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 60, 'Team Member', '1RG24CS038');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 61, 'Team Member', '1RG24CS402');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 62, 'Team Member', '1RG25CS401');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 63, 'Team Member', '1RG25CS404');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 64, 'Team Member', '1RG24CS010');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 65, 'Team Member', '1RG24CS109');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 66, 'Team Member', '1RG24CS097');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 67, 'Team Member', '1RG24CS103');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 68, 'Team Member', '1RG24CS083');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 69, 'Team Member', '1RG24CS087');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 70, 'Team Member', '1RG24CS106');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 71, 'Team Member', '1RG24CS112');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 72, 'Team Member', '1RG24CS090');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 73, 'Team Member', '1RG24CS104');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 74, 'Team Member', '1RG24CS091');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 75, 'Team Member', '1RG24CS064');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 76, 'Team Member', '1RG24CS0102');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 77, 'Team Member', '1RG24CS099');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 78, 'Team Member', '1RG24CS113');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 79, 'Team Member', '1RG24CS063');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 80, 'Team Member', '1RG24CS066');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 81, 'Team Member', '1RG24CS094');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 82, 'Team Member', '1RG24CS082');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 83, 'Team Member', '1RG24CS092');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 84, 'Team Member', '1RG24CS116');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 85, 'Team Member', '1RG24CS110');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 86, 'Team Member', '1RG24CS095');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 87, 'Team Member', '1RG24CS093');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 88, 'Team Member', '1RG24CS070');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 89, 'Team Member', '1RG24CSO58');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 90, 'Team Member', '1RG24CS098');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 91, 'Team Member', '1RG24CS096');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 92, 'Team Member', '1RG24CS088');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 93, 'Team Member', '1RG24CS108');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 94, 'Team Member', '1RG24CS078');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 95, 'Team Member', '1RG24CS080');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 96, 'Team Member', '1RG24CS075');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 97, 'Team Member', '1RG24CS059');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 98, 'Team Member', '1RG24CS115');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 99, 'Team Member', '1RG24CS114');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 100, 'Team Member', '1RG24CS073');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 101, 'Team Member', '1RG24CS072');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 102, 'Team Member', '1RG24CS079');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 103, 'Team Member', '1RG24CS067');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 104, 'Team Member', '1RG24CS077');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 105, 'Team Member', '1RG24CS117');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 106, 'Team Member', '1RG24CS084');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 107, 'Team Member', '1RG24CS100');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 108, 'Team Member', '1RG24CS076');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 109, 'Team Member', '1RG24CS071');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 110, 'Team Member', '1RG24CS111');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 111, 'Team Member', '1RG24CS086');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 112, 'Team Member', '1RG24CS105');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 113, 'Team Member', '1RG24CS081');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 114, 'Team Member', '1RG24CS107');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 115, 'Team Member', '1RG24CS041');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 116, 'Team Member', '1RG25CS400');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 117, 'Team Member', '1RG25CS405');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 118, 'Team Member', '1RG24CSO68');
INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (1, 119, 'Team Member', '1RG24CSO69');

-- 6. Create Projects and Assign Members
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (1, 1, 'AI BASED PHISHING DETECTION SYSTEM', 'Mini project for 5th semester.', 1, 2);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (1, 16, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (1, 17, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (1, 18, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (2, 1, '                   AI-POWERED TERMINAL ', 'Mini project for 5th semester.', 1, 3);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (2, 19, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (2, 20, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (2, 21, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (2, 22, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (3, 1, 'RESUME  BUILDER ', 'Mini project for 5th semester.', 1, 3);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (3, 23, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (3, 24, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (3, 25, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (3, 26, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (4, 1, 'DevPATH- AI POWERED PERSONALIZED LEARNING PLATFORM', 'Mini project for 5th semester.', 1, 4);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (4, 27, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (4, 28, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (4, 29, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (4, 30, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (5, 1, 'AI PROJECT ARCHITECT ', 'Mini project for 5th semester.', 1, 5);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (5, 31, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (5, 32, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (5, 33, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (5, 34, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (5, 69, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (6, 1, 'AI VOTER SHIELD ', 'Mini project for 5th semester.', 1, 6);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (6, 35, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (6, 36, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (6, 37, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (6, 38, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (6, 70, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (7, 1, 'AI BASED SOFTWARE BUG PREDICTION AND RISK ANALYSIS WEB APPLN', 'Mini project for 5th semester.', 1, 2);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (7, 39, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (7, 40, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (7, 41, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (7, 42, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (8, 1, 'REAL-TIME CROP DISEASE DETECTION AND REMEDIATION APP ', 'Mini project for 5th semester.', 1, 7);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (8, 43, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (8, 44, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (8, 45, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (8, 46, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (9, 1, 'OFFLINE ALGORITHM RECOMMENDATION AND COMPARISON SYSTEM', 'Mini project for 5th semester.', 1, 3);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (9, 47, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (9, 48, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (9, 49, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (9, 50, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (10, 1, 'Ai based voice/keyboard command assistant with search integration ', 'Mini project for 5th semester.', 1, 8);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (10, 51, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (10, 52, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (10, 53, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (11, 1, 'AI - powered document and image suite', 'Mini project for 5th semester.', 1, 9);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (11, 54, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (11, 55, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (11, 56, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (11, 57, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (12, 1, 'AI - Based Smart Travel Safety Assistance System', 'Mini project for 5th semester.', 1, 10);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (12, 58, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (12, 59, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (12, 60, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (13, 1, 'Smart Flight Price Comparison System', 'Mini project for 5th semester.', 1, 9);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (13, 61, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (13, 62, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (13, 63, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (13, 64, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (14, 1, 'Library Management system', 'Mini project for 5th semester.', 1, 8);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (14, 65, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (14, 66, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (14, 67, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (15, 1, 'Hostel Management System', 'Mini project for 5th semester.', 1, 7);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (15, 68, 1, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (16, 1, 'Placement management system', 'Mini project for 5th semester.', 1, 11);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (16, 71, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (16, 72, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (16, 73, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (17, 1, 'Vehicle parking management System', 'Mini project for 5th semester.', 1, 10);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (17, 74, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (17, 75, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (17, 76, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (17, 77, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (18, 1, 'Hospital Management System', 'Mini project for 5th semester.', 1, 9);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (18, 78, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (18, 79, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (18, 80, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (18, 81, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (19, 1, 'Internship and scholarship tracker', 'Mini project for 5th semester.', 1, 2);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (19, 82, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (19, 83, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (19, 84, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (20, 1, 'Smat site selection for wind turbines using AI techniques', 'Mini project for 5th semester.', 1, 12);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (20, 85, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (20, 86, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (20, 87, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (21, 1, 'Crop theft alert system', 'Mini project for 5th semester.', 1, 13);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (21, 88, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (21, 89, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (21, 90, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (21, 91, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (22, 1, 'School management system', 'Mini project for 5th semester.', 1, 14);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (22, 92, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (22, 93, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (22, 94, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (22, 95, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (23, 1, 'Project management system', 'Mini project for 5th semester.', 1, 14);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (23, 96, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (23, 97, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (23, 98, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (23, 99, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (24, 1, 'AI Interview preparation system', 'Mini project for 5th semester.', 1, 11);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (24, 100, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (24, 101, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (24, 102, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (24, 103, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (25, 1, 'Gas booking', 'Mini project for 5th semester.', 1, 5);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (25, 104, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (25, 105, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (25, 106, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (25, 107, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (26, 1, 'Code based attendance system', 'Mini project for 5th semester.', 1, 12);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (26, 108, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (26, 109, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (26, 110, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (26, 111, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (27, 1, 'Student skill and project matching system', 'Mini project for 5th semester.', 1, 11);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (27, 112, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (27, 113, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (27, 114, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (27, 115, 0, 'Active');
INSERT INTO projects (id, classroom_id, name, description, created_by, mentor_id) VALUES (28, 1, 'Capsule', 'Mini project for 5th semester.', 1, 8);
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (28, 116, 1, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (28, 117, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (28, 118, 0, 'Active');
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (28, 119, 0, 'Active');

-- ==========================================
-- 7. CURRENT-APP ADDITIONS
-- The classroom started 10 days ago and runs 10 weeks, so the demo is
-- always sitting in Week 2: Literature Survey (Week 1) is done, the
-- ==========================================

-- 7a. Weekly phases (1 phase = 1 week; teacher-facing labels)
INSERT INTO classroom_phases (classroom_id, week_number, label, merged_into_week) VALUES
(1, 1,  'Week 1: Synopsis & Literature Survey', NULL),
(1, 2,  'Week 2: System Architecture & Design', NULL),
(1, 3,  'Week 3: Database & Backend Setup', NULL),
(1, 4,  'Week 4: Core Module Implementation', NULL),
(1, 5,  'Week 5: Frontend Integration', NULL),
(1, 6,  'Week 6: Testing & Bug Fixes', NULL),
(1, 7,  'Week 7: Phase 2 Review', NULL),
(1, 8,  'Week 8: VTU Report Draft', NULL),
(1, 9,  'Week 9: Final Polish', NULL),
(1, 10, 'Week 10: Final Demo & Viva', NULL);

-- 7d. Marketplace: CHAITHRA AB is the only classmate without a team.
-- Sameer (Hostel Management System, solo team) has invited her.
INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (15, 15, 0, 'Invited');

-- 7e. Weekly logs, attachments, mentor reviews and attendance (Week 1)
INSERT INTO weekly_submissions (id, project_id, week_number, submitted_by, work_summary, next_steps, submitted_at) VALUES
(1, 1, 1, 16, 'Finalised the synopsis and reviewed 3 base papers on ML-based phishing URL detection. Shortlisted lexical and host-based features.', 'Draft the system architecture and start collecting the URL dataset.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 2, 1, 19, 'Read papers on AI shell assistants and wrote the problem statement.', 'Start the architecture diagram.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 23, 1, 96, 'Completed literature survey on academic project tracking tools and finalised the synopsis. Identified classroom, team and weekly-log modules.', 'Design the database schema and the Kanban board flow.', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- 7e. Saturday Guide Review Engine: Weekly Meetings, Guide Attendance, Changes & Instructions
INSERT INTO weekly_meetings (id, project_id, week_number, meeting_date, status, team_update, guide_feedback, submitted_by, submitted_at) VALUES
(1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'held', 'Finalised the synopsis and reviewed 3 base papers on ML-based phishing URL detection. Shortlisted lexical and host-based features.', 'Good start. Compare at least one deep-learning approach against the classical feature-based models.', 16, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 2, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'held', 'Read papers on AI shell assistants and wrote the problem statement. Need guidance on sandbox boundaries.', 'Summary is too brief. List the base papers and define execution boundary.', 19, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 23, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'held', 'Completed literature survey on academic project tracking tools and finalised the synopsis. Identified classroom, team and weekly-log modules.', 'Approved. Proceed with database schema and wireframes.', 96, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'scheduled', NULL, NULL, NULL, NULL),
(5, 2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'scheduled', NULL, NULL, NULL, NULL),
(6, 23, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'scheduled', NULL, NULL, NULL, NULL);

INSERT INTO weekly_submission_files (id, meeting_id, submission_id, original_name, stored_name, file_name, file_path, file_size, mime_type) VALUES
(1, 1, 1, 'Phishing_Detection_Literature_Survey.pdf', 'Phishing_Detection_Literature_Survey.pdf', 'Phishing_Detection_Literature_Survey.pdf', 'uploads/weekly/Phishing_Detection_Literature_Survey.pdf', 245800, 'application/pdf'),
(2, 3, 3, 'PMS_Synopsis_Week1.pdf', 'PMS_Synopsis_Week1.pdf', 'PMS_Synopsis_Week1.pdf', 'uploads/weekly/PMS_Synopsis_Week1.pdf', 182400, 'application/pdf');

INSERT INTO meeting_attendance (meeting_id, user_id, status, marked_by, marked_at) VALUES
(1, 16, 'present', 2, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 17, 'present', 2, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 18, 'present', 2, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 19, 'present', 3, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 20, 'present', 3, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 21, 'present', 3, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 22, 'excused', 3, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 96, 'present', 14, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 97, 'present', 14, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 98, 'present', 14, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 99, 'present', 14, DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO attendance_changes (meeting_id, user_id, old_status, new_status, changed_by, reason, changed_at) VALUES
(2, 22, 'absent', 'excused', 3, 'Submitted medical certificate for university health clinic visit', DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO guide_instructions (id, meeting_id, text, status, created_by, closed_at) VALUES
(1, 1, 'Compare Random Forest with a 1D-CNN baseline on the URL dataset', 'open', 2, NULL),
(2, 1, 'Collect minimum 50,000 legitimate and phishing URL samples', 'acknowledged', 2, NULL),
(3, 2, 'Define exact sandbox security boundaries for command execution', 'open', 3, NULL),
(4, 3, 'Prepare entity-relationship diagram for Saturday guide review', 'done', 14, DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO weekly_reviews (submission_id, reviewed_by, status, mentor_remarks, reviewed_at) VALUES
(1, 2,    'approved', 'Good start. Compare at least one deep-learning approach against the classical feature-based models.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 3,    'revision_needed', 'Summary is too brief. List the base papers you reviewed and the exact scope of the terminal assistant, then resubmit.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, NULL, 'pending', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO weekly_attendance (submission_id, user_id, present) VALUES
(1, 16, 1), (1, 17, 1), (1, 18, 1),
(2, 19, 1), (2, 20, 1), (2, 21, 1), (2, 22, 0),
(3, 96, 1), (3, 97, 1), (3, 98, 1), (3, 99, 1);

-- 7i. Notifications (header bell)
INSERT INTO notifications (user_id, type, message, link, is_read, created_at) VALUES
-- Coordinator + mentors
(14, 'weekly_log',   'Ranjith kumar submitted the Week 1 log for Project management system.', 'dashboard.php?classroom_id=1&project_id=23', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2,  'weekly_log',   'KEERTHANA submitted the Week 1 log for AI BASED PHISHING DETECTION SYSTEM.', 'dashboard.php?classroom_id=1&project_id=1', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
-- Project 1 (approved log)
(16, 'review',         'Your mentor approved the Week 1 log and left remarks.', 'dashboard.php?classroom_id=1', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(17, 'review',         'Your mentor approved the Week 1 log and left remarks.', 'dashboard.php?classroom_id=1', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(18, 'review',         'Your mentor approved the Week 1 log and left remarks.', 'dashboard.php?classroom_id=1', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
-- Project 2 (revision needed)
(19, 'review', 'Your mentor asked for a revision on the Week 1 log.', 'dashboard.php?classroom_id=1', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(20, 'review', 'Your mentor asked for a revision on the Week 1 log.', 'dashboard.php?classroom_id=1', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(21, 'review', 'Your mentor asked for a revision on the Week 1 log.', 'dashboard.php?classroom_id=1', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(22, 'review', 'Your mentor asked for a revision on the Week 1 log.', 'dashboard.php?classroom_id=1', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
-- Marketplace invite
(15, 'invite', 'You have been invited to join Hostel Management System.', 'dashboard.php?classroom_id=1', 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 7j. Classroom Milestones (Phase 5)
INSERT INTO classroom_milestones (classroom_id, title, due_date) VALUES
(1, 'Synopsis & Problem Statement Approval', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(1, 'Phase 1 Architecture & Design Review', DATE_ADD(CURDATE(), INTERVAL 14 DAY)),
(1, 'Mid-Term Progress Viva & Code Inspection', DATE_ADD(CURDATE(), INTERVAL 35 DAY)),
(1, 'Final Demonstration & VTU Report Submission', DATE_ADD(CURDATE(), INTERVAL 56 DAY));

-- 7k. Evaluation Marks Sheet (Phase 6)
-- Project 1 (AI Based Phishing Detection): Finalized by Guide (Roopa, user 2)
INSERT INTO project_marks (project_id, report_marks, is_finalized, finalized_by, finalized_at, updated_by, updated_at) VALUES
(1, 46.50, 1, 2, DATE_SUB(NOW(), INTERVAL 1 DAY), 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 42.00, 0, NULL, NULL, 3, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Individual Student Marks for Project 1 (Leader: KEERTHANA 16, Members: ANKITHA 17, ARCHANA 18)
INSERT INTO student_marks (project_id, user_id, presentation_marks, qa_marks, updated_by, updated_at) VALUES
(1, 16, 23.50, 24.00, 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 17, 22.00, 23.50, 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 18, 21.50, 22.00, 2, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Individual Student Marks for Project 2 (Leader: LOKESH 19, Members: MANJUNATH 20, MANYA 21, BHARGAVI 22)
INSERT INTO student_marks (project_id, user_id, presentation_marks, qa_marks, updated_by, updated_at) VALUES
(2, 19, 21.00, 20.50, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 20, 20.00, 21.00, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 21, 19.50, 20.00, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 22, 18.00, 17.50, 3, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Post-finalization adjustment audit log entry
INSERT INTO marks_changes (project_id, user_id, field_name, old_value, new_value, changed_by, reason, changed_at) VALUES
(1, 16, 'presentation_marks', '22.50', '23.50', 1, 'Re-evaluated presentation slide delivery after coordinator review', DATE_SUB(NOW(), INTERVAL 12 HOUR));

