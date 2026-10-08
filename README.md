# PMS — Academic Project Review & Continuous Evaluation Engine

PMS is a lean, guide-centered academic project management platform built in native PHP 8.x, MySQL/MariaDB, Tailwind CSS, and Vanilla JavaScript, tailored specifically for engineering college project curriculums (including VTU).

The system centers on the **Saturday Guide Meeting** as the core operational record for Continuous Internal Evaluation (CIE), student attendance compliance, actionable mentor directives, and external viva voce preparation.

---

## Key Features

- **Classroom Isolation & Contextual Roles**: Workspaces organized by class section with contextual permissions per classroom (`Admin` / Teacher / Coordinator, `Team Member` / Project Leader / Student). No global role assumptions.
- **Saturday Guide Review Engine**: Weekly review meetings auto-derived from classroom semester dates (`start_date` to `end_date`), capturing structured progress (`work_done`, `next_steps`, `blockers`), secure file attachments, and append-only guide feedback history.
- **Guide-Controlled Attendance**: Fast 1-click batch entry for guides to mark present, absent, or excused for all team members in ~10 seconds. Post-save edits enforce a mandatory justification reason and are immutably logged to `attendance_changes`. Features university shortage alert (<75%) on student dashboards.
- **Actionable Mentor Directives & Rollover**: Faculty guides issue specific action items (`guide_instructions`) with a full lifecycle (`open`, `acknowledged`, `done`) and cross-week rollover until formally closed.
- **Classroom Milestones & Live Countdowns**: Coordinator-defined deadlines (e.g. Synopsis Approval, Mid-Term Viva, Final Report Submission) with dynamic countdown banners displayed across all role dashboards.
- **Continuous Internal Evaluation (CIE) Marks Sheet**: Config-driven grading scheme (50 Project Report / 25 Presentation / 25 Viva Q&A = 100 Total), finalize lock mechanism, Admin-only modification audits with mandatory reason logging, and exportable printable official department marks ledger & CSV sheets.
- **VTU Project Report Assistant**: Swappable `.docx` Word report template pre-filled with project metadata, team members & USNs, faculty guide, HOD, Saturday weekly guide meeting review logs, action directives, attendance compliance, and CIE evaluation marks.
- **Team Marketplace & Team Size Guard**: Students browse projects, submit join requests, withdraw requests, or accept leader invitations, with strict server-side team size enforcement (maximum of 4 active members and minimum of 1).
- **Notification Bell**: Unread badge dropdown alerting users of join requests, invitations, mentor reviews, and evaluation marks updates.

---

## Database Architecture

The database schema (`schema.sql`) represents the lean academic review engine:
1. `users` — Authentication credentials and display names.
2. `classrooms` — Isolated course/section workspaces with date schedules and team size boundaries.
3. `classroom_members` — Contextual roles (`Admin` or `Team Member`) and USNs.
4. `projects` — Academic project entities scoped to a classroom with assigned faculty mentor.
5. `project_members` — Team rosters with leader indicators and join statuses.
6. `classroom_phases` — Schedule-derived academic weeks (coordinator-renamable and mergeable).
7. `classroom_milestones` — Timeline evaluation targets with countdown calculation.
8. `weekly_meetings` — Saturday guide meetings with team updates, guide feedback, and status (`scheduled`, `held`, `rescheduled`, `holiday`).
9. `meeting_attendance` — Guide-controlled student attendance (`present`, `absent`, `excused`).
10. `attendance_changes` — Append-only immutable audit trail for post-meeting attendance adjustments.
11. `guide_instructions` — Actionable mentor directives with rollover and acknowledgement lifecycle.
12. `meeting_feedback_history` — Append-only guide feedback history across review iterations.
13. `weekly_submission_files` — Meeting file attachments with MIME inspection and secure storage.
14. `project_marks` — Continuous Internal Evaluation shared project report marks (/50) with finalize lock.
15. `student_marks` — Individual student presentation marks (/25) and viva Q&A marks (/25).
16. `marks_changes` — Append-only immutable audit trail for post-finalization marks adjustments.
17. `notifications` — System event notifications for reviews, invitations, and requests.
18. `weekly_submissions`, `weekly_reviews`, `weekly_attendance` — Maintained for backward compatibility.

---

## How to Set Up and Run

1. **Prerequisites**:
   - [XAMPP](https://www.apachefriends.org/) with Apache and MySQL / MariaDB (PHP 8.x).
   - Python 3.x with `docxtpl` for Word report generation (`pip install docxtpl`).
   - Start **Apache** and **MySQL** from the XAMPP Control Panel.

2. **Placement**:
   - Clone or copy this repository into your XAMPP web root:
     `C:\xampp\htdocs\pjtmgmt`

3. **Database Setup**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin/`) or MySQL CLI:
     ```bash
     mysql -u root -p < schema.sql
     ```
   - *(Optional for evaluation/testing)*: Import `demo_seed.sql` to populate sample classrooms, faculty guides, 28 real project teams, Saturday meetings, attendance, instructions, milestones, and CIE marks:
     ```bash
     mysql -u root -p < demo_seed.sql
     ```
   - Verify connection settings in `dbs.php` (default: host `localhost`, user `root`, password ``, database `pms`).

4. **Running the Application**:
   - Open `http://localhost/pjtmgmt/login.php` in your browser.
   - **Demo Credentials** (all accounts use password `password123`):
     - **Coordinator / Admin**: `Coordinator Admin`
     - **Faculty Guides**: `Roopa`, `Nanda Kumar`, `NaghaLakshmi`, `Latha P H`
     - **Student Leaders**: `KEERTHANA`, `LOKESH N KULER`, `ADHITYA G N`

5. **Running Automated Tests**:
   - Execute the test suite via PHP CLI:
     ```bash
     php tests/run.php
     ```
   - All tests run in-memory against isolated SQLite fixtures covering authorization guards, meeting engines, attendance audit trails, milestones, team size guards, evaluation marks calculations, and report docx generation.
