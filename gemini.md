# PMS: Academic Project Review & Continuous Evaluation System
> **Target Platform**: Native PHP 8.x + MySQL (PDO) + Tailwind CSS (CDN) + Vanilla JS  
> **Environment**: Out-of-the-box local XAMPP (`C:\xampp\htdocs\oneshot`)

---

## 1. Environment & Execution Guidelines (User-Defined Protocol)

* **Database Import Policy**:
  * **Never import schema or seed files autonomously via CLI.**
  * The user reviews `schema.sql` and `demo_seed.sql` directly and imports them manually into phpMyAdmin or MySQL CLI.
* **XAMPP Local Binary Paths**:
  * PHP executable: `C:\xampp\php\php.exe` (XAMPP default, may not be in system `PATH`).
  * MySQL service: Running on `localhost:3306`, user `root`, password `""` (empty), database `pms`.
* **Standard Bcrypt Password Hash for `password123`**:
  * Hash string: `$2y$10$nwFfssPTvmDl9MxK2pAEze.9g5CqnZzibkx6OkeGp.RYNqHgkQZXK`
  * Use this exact verified hash in `demo_seed.sql` and test fixtures so logins work reliably out-of-the-box.

---

## 2. Core Architecture & High-Value Features

### A. Role-Based Access Control (RBAC) & Registration
* **Student Registration**: Full Name, Username, Password, mandatory **USN** (e.g., `1MS21CS042`), `role = 'student'`.
* **Teacher Registration**: Full Name, Username, Password, mandatory **Staff ID** (e.g., `CS-FAC-101`), `role = 'teacher'`.
* **Auth Guard (`auth_guard.php`)**: Protects routes based on `$_SESSION['role']`. Teacher-only endpoints reject unauthorized access with HTTP 403. `dashboard.php` routes teachers to `views/teacher.php` and students to `views/student.php`.

### B. Multi-Institution Classroom Isolation via Unique Invite Codes
* **Classroom Creation**: Teachers create classrooms with Title, Institution Name (e.g. *RV College of Engineering*), Department (e.g. *Computer Science & Engineering*), Semester Start Date, and Semester End Date.
* **Clash-Proof Invite Code**: Generates short 6-character code (e.g., `RV-CS6B`).
* **Student Enrollment**: Students enter the invite code on `hub.php` to join their class section. Multi-college data remains isolated.
* **Dynamic College Letterhead**: Classroom metadata populates official marks sheets and ledgers.

### C. Saturday Guide Review Engine (Temporal Week Locking)
* Derived from classroom start and end dates (all Saturdays mapped week-by-week).
* **Strict Temporal Locking**:
  * **Future Weeks (`meeting_date > today`)**:
    * **Student Lock**: Cannot submit ahead of time. Badge: 🔒 *"Unlocks on [date_from]"*.
    * **Teacher Lock**: Cannot record advance attendance (`save_meeting_attendance.php` strictly rejects dates > today).
  * **Current Active Week (`today` between `date_from` and `meeting_date`)**:
    * Open for student logs (`work_done`, `next_steps`, `blockers`).
    * Open for guide remarks, meeting status (`held`, `holiday`, `rescheduled`), and batch attendance.
  * **Past Weeks (`meeting_date < today` or status is `held`)**:
    * **Student Lock**: Read-only archive. No retrospective tampering.
    * **Attendance Frozen**: Modifications require a mandatory audit justification logged in `attendance_changes`.
* **Mentor Directives with Rollover**:
  * Guides issue action items (`guide_instructions`). Lifecycle: `open` &rarr; `acknowledged` &rarr; `done`.
  * Open directives automatically roll over to subsequent reviews until resolved.

### D. Guide Attendance & 75% Shortage Alert
* 10-second batch entry for team members (`present`, `absent`, `excused`).
* Automatic calculation of cumulative attendance.
* Prominent visual warning badge if attendance falls below **75%**.

### E. Teacher Project Content Management (Media Showcase)
* Student uploads: UI screenshots, hardware/lab setup photos, codebase URLs (GitHub/GitLab), demo video links (YouTube/Drive).
* Stored in `uploads/projects/{project_id}/` with secure MIME checks and random hashed filenames. Script execution blocked via `.htaccess`.
* Faculty showcase gallery with lightbox modal and spotlight toggle (curate top projects for exhibition reels).

### F. Continuous Internal Evaluation (CIE) Marks Engine & Department Ledger
* **Assessment Split**:
  * **50 Marks**: Project Report & Documentation (team score in `project_marks`).
  * **25 Marks**: Presentation & Technical Demo (individual score in `student_marks`).
  * **25 Marks**: Viva Voce & Technical Q&A (individual score in `student_marks`).
  * **Total**: 100 Marks.
* **Side-by-Side Scoring Modal**: Teachers view project assets and code links alongside grade inputs.
* **Finalize Lock**: Prevents accidental edits; post-finalization adjustments require teacher justification recorded in `marks_changes`.
* **Printable A4 Landscape Ledger**: Formal marks ledger with dynamic College & Department letterhead, attendance %, marks breakdown, and signature blocks for Guide, Project Coordinator, and HOD.
* **One-Click CSV Export**: Structured export formatted for direct marks entry.

---

## 3. Database Schema Overview

* `users`: `id`, `name`, `username`, `password`, `role` (`student`|`teacher`), `identifier` (USN / Staff ID), `created_at`.
* `classrooms`: `id`, `name`, `institution`, `department`, `teacher_id`, `invite_code`, `start_date`, `end_date`, `created_at`.
* `classroom_students`: `classroom_id`, `student_id`, `enrolled_at`.
* `projects`: `id`, `classroom_id`, `title`, `description`, `created_by` (leader), `mentor_id` (guide), `created_at`.
* `project_members`: `project_id`, `student_id`, `is_leader`. (1–4 members enforced).
* `project_assets`: `id`, `project_id`, `uploaded_by`, `asset_type` (`screenshot`|`photo`|`codebase`|`demo_link`), `title`, `caption`, `file_path`, `external_url`, `is_spotlight`, `created_at`.
* `weekly_meetings`: `id`, `project_id`, `week_number`, `meeting_date`, `status` (`scheduled`|`held`|`rescheduled`|`holiday`), `team_update` (JSON), `guide_feedback`, `submitted_by`, `submitted_at`, `created_at`.
* `meeting_attendance`: `meeting_id`, `student_id`, `status` (`present`|`absent`|`excused`), `marked_by`, `marked_at`.
* `attendance_changes`: `id`, `meeting_id`, `student_id`, `old_status`, `new_status`, `changed_by`, `reason`, `changed_at`.
* `guide_instructions`: `id`, `meeting_id`, `text`, `status` (`open`|`acknowledged`|`done`), `created_by`, `closed_at`, `created_at`.
* `project_marks`: `project_id`, `report_marks` (out of 50), `is_finalized`, `finalized_by`, `finalized_at`, `updated_by`, `updated_at`.
* `student_marks`: `project_id`, `student_id`, `presentation_marks` (out of 25), `qa_marks` (out of 25), `updated_by`, `updated_at`.
* `marks_changes`: `id`, `project_id`, `student_id`, `field_name`, `old_value`, `new_value`, `changed_by`, `reason`, `changed_at`.

---

## 4. File Map

```
oneshot/
├── config/
│   └── evaluation.php               # Assessment weights (50/25/25) & attendance rule
├── dbs.php                          # Clean PDO MySQL connection ($pdo)
├── auth_guard.php                   # Role checks (is_teacher(), is_student()) & session helper
├── login.php & logout.php           # Authentication with quick-test demo buttons
├── registers.php                    # Student (USN) / Teacher (Staff ID) registration
├── hub.php                          # Classroom overview & join by 6-char invite code
├── create_classroom.php             # Teacher creates classroom with institution & code
├── create_project.php               # Student creates team (1-4) & assigns mentor
├── dashboard.php                    # Role router (teacher.php vs student.php)
│
├── meeting_engine.php               # Saturday review schedule derivation & temporal locks
├── save_meeting_attendance.php      # 1-Click batch attendance & audit logger
├── submit_weekly_log.php            # Saturday progress submission (work/next/blockers)
├── manage_instruction.php           # Mentor directives & rollover lifecycle
│
├── upload_project_asset.php         # Student asset uploader (media/code links)
├── manage_project_asset.php         # Teacher asset curation & exhibition spotlight
│
├── save_marks.php                   # CIE 50/25/25 scoring, validation & finalize lock
├── export_marks.php                 # Printable A4 marks ledger & CSV export
├── schema.sql                       # Clean MySQL schema (reviewed & imported by user)
├── demo_seed.sql                    # Demo data with verified bcrypt password123 hashes
│
├── views/
│   ├── header.php & footer.php      # Tailwind CDN, Inter typography, navigation
│   ├── teacher.php                  # Teacher workspace (reviews, gallery, grading)
│   ├── student.php                  # Student workspace (updates, uploads, shortage badge)
│   ├── marks_modal.php              # Live scoring modal with asset preview
│   ├── media_modal.php              # Lightbox viewer for screenshots and photos
│   └── attendance_modal.php         # 10-second batch attendance modal
│
└── uploads/
    └── projects/                    # Image storage (.htaccess protected)
```

---

## 5. Verified Demo Test Accounts (Password: `password123`)

* **Teacher**:
  * Username: `roopa` | Password: `password123` | Name: `Prof. Roopa M` | Staff ID: `CS-FAC-101`
* **Student Leader**:
  * Username: `keerthana` | Password: `password123` | Name: `Keerthana R` | USN: `1MS21CS042`
* **Student Teammates**:
  * Username: `lokesh` | Password: `password123` | Name: `Lokesh N` | USN: `1MS21CS048`
  * Username: `adhitya` | Password: `password123` | Name: `Adhitya G` | USN: `1MS21CS012`
* **Classroom**: `7th Sem Major Project (Sec A)` | Code: `RV-CS6B` | `RV College of Engineering` (Term: Aug 2026 – Dec 2026)
* **Project**: `Smart IoT Traffic Congestion Controller`
