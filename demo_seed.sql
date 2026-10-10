-- PMS Realistic Demo Seed Data
-- Target Database: pms
-- Password for all pre-seeded accounts: password123
-- Verified Bcrypt Hash: $2y$10$nwFfssPTvmDl9MxK2pAEze.9g5CqnZzibkx6OkeGp.RYNqHgkQZXK
-- Aligned with the current active semester (August to December 2026)

CREATE DATABASE IF NOT EXISTS `pms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pms`;

-- Disable Foreign Key checks temporarily for clean reseeding
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM marks_changes;
DELETE FROM student_marks;
DELETE FROM project_marks;
DELETE FROM guide_instructions;
DELETE FROM attendance_changes;
DELETE FROM meeting_attendance;
DELETE FROM weekly_meetings;
DELETE FROM project_assets;
DELETE FROM project_members;
DELETE FROM projects;
DELETE FROM classroom_students;
DELETE FROM classrooms;
DELETE FROM users;

ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE classrooms AUTO_INCREMENT = 1;
ALTER TABLE projects AUTO_INCREMENT = 1;
ALTER TABLE project_assets AUTO_INCREMENT = 1;
ALTER TABLE weekly_meetings AUTO_INCREMENT = 1;
ALTER TABLE attendance_changes AUTO_INCREMENT = 1;
ALTER TABLE guide_instructions AUTO_INCREMENT = 1;
ALTER TABLE marks_changes AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Insert Users (1 Teacher Coordinator/Mentor, 4 Students)
INSERT INTO users (id, name, username, password, role, identifier) VALUES
(1, 'Prof. Roopa M', 'roopa', '$2y$10$nwFfssPTvmDl9MxK2pAEze.9g5CqnZzibkx6OkeGp.RYNqHgkQZXK', 'teacher', 'CS-FAC-101'),
(2, 'Keerthana R', 'keerthana', '$2y$10$nwFfssPTvmDl9MxK2pAEze.9g5CqnZzibkx6OkeGp.RYNqHgkQZXK', 'student', '1MS21CS042'),
(3, 'Lokesh N', 'lokesh', '$2y$10$nwFfssPTvmDl9MxK2pAEze.9g5CqnZzibkx6OkeGp.RYNqHgkQZXK', 'student', '1MS21CS048'),
(4, 'Adhitya G', 'adhitya', '$2y$10$nwFfssPTvmDl9MxK2pAEze.9g5CqnZzibkx6OkeGp.RYNqHgkQZXK', 'student', '1MS21CS012'),
(5, 'Rahul V', 'rahul', '$2y$10$nwFfssPTvmDl9MxK2pAEze.9g5CqnZzibkx6OkeGp.RYNqHgkQZXK', 'student', '1MS21CS075');

-- 2. Insert Active Classroom (August 1, 2026 to December 15, 2026)
INSERT INTO classrooms (id, name, institution, department, teacher_id, invite_code, start_date, end_date) VALUES
(1, '7th Sem Major Project (Sec A)', 'RV College of Engineering, Bengaluru', 'Department of Computer Science & Engineering', 1, 'RV-CS6B', '2026-08-01', '2026-12-15');

-- 3. Classroom Enrollments
INSERT INTO classroom_students (classroom_id, student_id) VALUES
(1, 2),
(1, 3),
(1, 4),
(1, 5);

-- 4. Projects (Smart IoT Traffic Congestion Controller)
INSERT INTO projects (id, classroom_id, title, description, created_by, mentor_id) VALUES
(1, 1, 'Smart IoT Traffic Congestion Controller', 'Adaptive traffic light timing using computer vision and edge IoT sensors to minimize intersection waiting time and prioritize emergency vehicles.', 2, 1);

-- 5. Project Members (1 Leader + 2 batchmates, hard cap 4)
INSERT INTO project_members (project_id, student_id, is_leader) VALUES
(1, 2, 1), -- Keerthana R (Leader)
(1, 3, 0), -- Lokesh N
(1, 4, 0); -- Adhitya G

-- 6. Project Assets (Screenshots, Prototypes, Codebase & Demo links)
INSERT INTO project_assets (id, project_id, uploaded_by, asset_type, title, caption, file_path, external_url, is_spotlight) VALUES
(1, 1, 2, 'screenshot', 'System Architecture & Data Flow', 'End-to-end edge pipeline diagram connecting camera sensors to micro-controller relays.', 'uploads/projects/sample_architecture.png', NULL, 1),
(2, 1, 2, 'photo', 'Hardware Breadboard Prototype', 'ESP32 microcontroller wired with 4-way traffic LED relays and ultrasonic queue detector.', 'uploads/projects/sample_hardware.png', NULL, 1),
(3, 1, 2, 'codebase', 'GitHub Codebase Repository', 'Official repository including YOLOv8 fine-tuning weights and firmware scripts.', NULL, 'https://github.com/rvce-cse/iot-smart-traffic', 0),
(4, 1, 2, 'demo_link', 'Live Working Viva Demo Video', 'Video recording demonstrating emergency vehicle lane clearing on simulated 4-way junction.', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 0);

-- 7. Saturday Weekly Meetings (August to December 2026)
-- Weeks 1-9: Past Held Meetings
-- Week 10 (2026-10-10): Active Current Review Week (Open for live submission & evaluation)
-- Weeks 11-19: Future Meetings (Strictly Locked)
INSERT INTO weekly_meetings (id, project_id, week_number, meeting_date, status, team_update, guide_feedback, submitted_by, submitted_at) VALUES
(1, 1, 1, '2026-08-08', 'held', 
 '{"work_done":"Completed literature survey on existing fixed-timer traffic controllers and procured ESP32 boards.","next_steps":"Design hardware circuit schematic and setup OpenCV traffic feed.","blockers":"None"}', 
 'Solid literature survey. Proceed with circuit schematic approval.', 2, '2026-08-07 17:30:00'),

(2, 1, 2, '2026-08-15', 'held', 
 '{"work_done":"Hardware circuit diagram finalized. Trained custom YOLOv8 model on Indian traffic dataset with 89% mAP.","next_steps":"Interface camera feed to ESP32 over serial communication.","blockers":"Minor serial baud rate sync errors."}', 
 'Detection accuracy looks promising. Resolve serial buffer overflow before next Saturday.', 2, '2026-08-14 18:15:00'),

(3, 1, 3, '2026-08-22', 'held', 
 '{"work_done":"Resolved serial latency. Implemented dynamic green-light duration calculation based on vehicle density.","next_steps":"Test emergency siren override using audio frequency detector.","blockers":"Awaiting audio sensor module shipment."}', 
 'Good progress on density algorithm. Ensure fail-safe default timing if camera disconnects.', 2, '2026-08-21 16:45:00'),

(4, 1, 4, '2026-08-29', 'held', 
 '{"work_done":"Integrated ultrasonic vehicle queue length backup sensors. Fabricated 3D printed model intersection enclosure.","next_steps":"Draft Phase 1 documentation and prepare presentation slides.","blockers":"None"}', 
 'Team demonstrates disciplined execution. Prepare for Phase 1 demo.', 2, '2026-08-28 19:00:00'),

(5, 1, 5, '2026-09-05', 'held', 
 '{"work_done":"Completed end-to-end integration test with emergency ambulance priority lane clearing.","next_steps":"Measure latency benchmarks and prepare final documentation.","blockers":"None"}', 
 'Excellent demo. Documentation format must follow university IEEE guidelines.', 2, '2026-09-04 18:00:00'),

(6, 1, 6, '2026-09-12', 'held', 
 '{"work_done":"Conducted vehicle speed estimation module testing under overcast lighting.","next_steps":"Optimize frame drop handling in edge pipeline.","blockers":"None"}', 
 'Maintain consistent frame capture rate.', 2, '2026-09-11 17:00:00'),

(7, 1, 7, '2026-09-19', 'held', 
 '{"work_done":"Implemented edge watchdog restart timer and fallback static timing matrix.","next_steps":"Calibrate night vision camera sensors.","blockers":"Low lux camera grain."}', 
 'Hardware fail-safe meets project milestone requirements.', 2, '2026-09-18 16:30:00'),

(8, 1, 8, '2026-09-26', 'held', 
 '{"work_done":"Integrated cloud telemetry logger for municipal congestion heatmaps via MQTT.","next_steps":"Finalize report chapter 3 and interim presentation deck.","blockers":"None"}', 
 'MQTT message structure is clean. Ensure TLS certificates for edge telemetry.', 2, '2026-09-25 18:20:00'),

(9, 1, 9, '2026-10-03', 'held', 
 '{"work_done":"Completed preliminary benchmark comparisons against conventional inductive loop sensors.","next_steps":"Prepare working model for live faculty evaluation.","blockers":"None"}', 
 'Benchmarking data is persuasive. Ready for active Phase 2 review.', 2, '2026-10-02 19:10:00'),

-- Week 10: TODAY (Active Current Week - Open for live submission & review)
(10, 1, 10, '2026-10-10', 'scheduled', NULL, NULL, NULL, NULL),

-- Weeks 11-19: Future Weeks (Strictly Locked)
(11, 1, 11, '2026-10-17', 'scheduled', NULL, NULL, NULL, NULL),
(12, 1, 12, '2026-10-24', 'scheduled', NULL, NULL, NULL, NULL),
(13, 1, 13, '2026-10-31', 'scheduled', NULL, NULL, NULL, NULL),
(14, 1, 14, '2026-11-07', 'scheduled', NULL, NULL, NULL, NULL),
(15, 1, 15, '2026-11-14', 'scheduled', NULL, NULL, NULL, NULL),
(16, 1, 16, '2026-11-21', 'scheduled', NULL, NULL, NULL, NULL),
(17, 1, 17, '2026-11-28', 'scheduled', NULL, NULL, NULL, NULL),
(18, 1, 18, '2026-12-05', 'scheduled', NULL, NULL, NULL, NULL),
(19, 1, 19, '2026-12-12', 'scheduled', NULL, NULL, NULL, NULL);

-- 8. Meeting Attendance for Past Held Meetings (Weeks 1 to 9)
-- Weeks 1-2 (All Present)
INSERT INTO meeting_attendance (meeting_id, student_id, status, marked_by) VALUES
(1, 2, 'present', 1), (1, 3, 'present', 1), (1, 4, 'present', 1),
(2, 2, 'present', 1), (2, 3, 'present', 1), (2, 4, 'present', 1);

-- Week 3 (Adhitya Excused)
INSERT INTO meeting_attendance (meeting_id, student_id, status, marked_by) VALUES
(3, 2, 'present', 1), (3, 3, 'present', 1), (3, 4, 'excused', 1);

-- Weeks 4-9 (All Present)
INSERT INTO meeting_attendance (meeting_id, student_id, status, marked_by) VALUES
(4, 2, 'present', 1), (4, 3, 'present', 1), (4, 4, 'present', 1),
(5, 2, 'present', 1), (5, 3, 'present', 1), (5, 4, 'present', 1),
(6, 2, 'present', 1), (6, 3, 'present', 1), (6, 4, 'present', 1),
(7, 2, 'present', 1), (7, 3, 'present', 1), (7, 4, 'present', 1),
(8, 2, 'present', 1), (8, 3, 'present', 1), (8, 4, 'present', 1),
(9, 2, 'present', 1), (9, 3, 'present', 1), (9, 4, 'present', 1);

-- 9. Actionable Guide Directives (With Rollover lifecycle)
INSERT INTO guide_instructions (id, meeting_id, text, status, created_by, closed_at) VALUES
(1, 1, 'Validate camera edge performance under low-light and rain simulation conditions.', 'done', 1, '2026-08-15 11:30:00'),
(2, 2, 'Measure memory footprint of the YOLO model on the edge device to prevent heat throttling.', 'acknowledged', 1, NULL),
(3, 3, 'Implement hard-coded fail-safe timer sequence in case edge camera stops responding.', 'done', 1, '2026-09-05 12:00:00'),
(4, 9, 'Include latency benchmark comparison table against conventional static timer in Report Chapter 4.', 'open', 1, NULL);

-- 10. Continuous Internal Evaluation (CIE) Marks
-- Shared Project Report Mark (out of 50)
INSERT INTO project_marks (project_id, report_marks, is_finalized, finalized_by, finalized_at, updated_by) VALUES
(1, 44.50, 0, NULL, NULL, 1);

-- Individual Student Marks (Presentation / 25, Viva Q&A / 25)
INSERT INTO student_marks (project_id, student_id, presentation_marks, qa_marks, updated_by) VALUES
(1, 2, 22.00, 23.50, 1), -- Keerthana: 44.5 + 22.0 + 23.5 = 90.00 / 100
(1, 3, 21.00, 22.00, 1), -- Lokesh:    44.5 + 21.0 + 22.0 = 87.50 / 100
(1, 4, 20.50, 21.00, 1); -- Adhitya:   44.5 + 20.5 + 21.0 = 86.00 / 100
