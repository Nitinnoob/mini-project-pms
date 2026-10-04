#  PMS — Full Session Handoff Summary

> **Generated**: 2026-09-27
> **Purpose**: Complete context for starting a new conversation. Read this + `GEMINI.md` in the repo root.

---

## 1. Project Overview

**PMS** is a PHP/MySQL academic project management platform for VTU engineering students. Teams of students work on semester mini-projects, guided by faculty mentors, and overseen by a coordinator. The system provides Kanban boards, team management, a project marketplace, a weekly mentor review and attendance system, and automated VTU project report generation.

**Stack**: PHP 8.x (procedural + PDO), MySQL/MariaDB, Tailwind CSS CDN, FontAwesome, SortableJS, Chart.js, Vanilla JS, Python 3.12 (`python-docx`, `docxtpl`).  
**Runtime**: XAMPP (Apache), served from `C:\xampp\htdocs\pjtmgmt2`.  
**Workspace**: `D:\1University\5thsem\3_Personal\pjtmgmt` (development/versioning directory).

---

## 2. User's Workflow Rules (CRITICAL — Always Follow)

1. **Do not write code as soon as you think you should.** Always plan first, execute on explicit command.
2. **The user copies code manually to XAMPP** for testing. The workspace is the versioning/archiving directory, not the live server.
3. **Cross-validation**: The user shares Claude (web AI) feedback and asks us to verify/reason before acting.
4. **Demo folders are archived snapshots** (`demo/`, `v 1.0.0/`). Don't modify them.
5. **`refactor.php` is dead code** — user will clean up later.
6. **`schema.sql` is the single source of truth** for all DB architecture.

---

## 3. Architecture: How the Codebase Works

### Contextual Roles
A single user account can be an **Admin** (Teacher/Mentor) in one classroom and a **Team Member** (Student) in another. Roles live in `classroom_members.role`, not globally.

### The `$viewData` Pattern
`dashboard.php` is the **master controller**. It packs everything into a `$viewData` associative array. View files (`views/*.php`) only read from `$viewData`. No view file queries the database directly.

### View Routing
```
dashboard.php
├── views/header.php        (always)
├── views/progress_bar.php  (always)
├── views/student.php       (if Student)
├── views/marketplace.php   (if Marketplace / unassigned student)
├── views/leader.php        (if Project Leader OR TeacherDrilldown)
├── views/teacher.php       (if Teacher/Admin without project_id)
├── views/sidebar.php       (if Student or Leader, not newly created)
├── views/add_task_modal.php
├── views/upload_modal.php
├── views/weekly_log_modal.php
├── views/mentor_review_modal.php  (only if isTeacherDrilldown)
└── views/footer.php        (always)
```

### CSS Themes & Dark Mode
`pms.css` switches themes via CSS variables (`--accent`, `--accent-2`) and a global dark mode toggle. 
- Dark mode toggle sets `class="dark"` on `<html>` and dispatches a `themeToggled` event.
- `dashboard.js` listens to `themeToggled` to dynamically re-read computed CSS variables and update Chart.js canvas elements immediately without page refresh.

---

## 4. Complete File Map

### Core PHP Files
| File | Purpose |
|---|---|
| `dbs.php` | PDO MySQL connection singleton |
| `schema.sql` | All 13 table DDL definitions (single source of truth) |
| `login.php`, `registers.php`, `logout.php` | Auth and session management |
| `hub.php` | Central portal: list classrooms, join/create |
| `dashboard.php` | **Master controller** — routes to all views |
| `create_classroom.php` | Create classroom with invite code, team sizes, dates |
| `join.php` | Join classroom via invite code, USN validation |
| `create_subproject.php` | Student creates project, becomes leader |
| `add_task.php`, `update_task_status.php` | Kanban task management |
| `request_join.php`, `manage_join_request.php` | Team join workflows |
| `upload_deliverable.php` | File upload with extension whitelist + `.htaccess` |
| `assign_mentor.php`, `add_mentor_to_classroom.php` | Mentor assignment and faculty invites |
| `submit_weekly_log.php`, `submit_mentor_review.php` | Weekly reviews and attendance workflows |

### View Files (`views/`)
| File | Purpose |
|---|---|
| `header.php`, `footer.php` | Shared layout elements, dark mode toggle, assets |
| `progress_bar.php` | Top progress bar (responsive grid) |
| `student.php` | Student kanban workbench |
| `leader.php` | Leader control room: kanban + team + weekly logs |
| `teacher.php` | Teacher marking desk: group ledger + faculty roster |
| `marketplace.php` | Project marketplace for unassigned students |
| `sidebar.php` | Contribution tracker, heatmap, audit log |
| `*_modal.php` | Modals for tasks, uploads, logs, and mentor reviews |

### Report Generator & Widgets (`code snippet/`)
| File | Purpose |
|---|---|
| `generate_group_report.php` | Authenticated endpoint fetching project metadata, members & USNs from DB |
| `generate_docx.php` | Secure PHP-to-Python bridge (`proc_open`) generating `.docx` reports |
| `generate_report.py` | Pure `docxtpl` VTU report generator binding student metadata to `report_template.docx` |
| `templates/report_template.docx` | Master Word template with dual section borders (certificates double, body single), running header/footer, and academic scaffolding placeholders |
| `attendance_tracker.php` | Saturday meeting attendance card with 80% mark deduction alert |
| `member_attendance_chart.php` | Per-member horizontal bar chart comparing attendance percentages |
| `revision_checklist.php` | Guide change compliance checklist with progress tracking |
| `report_readiness_widget.php` | Draft report deadline countdown & section readiness donut chart |

### JavaScript
| File | Purpose |
|---|---|
| `assets/js/dashboard.js` | Kanban drag-drop, tab switching, live Chart.js theme refresh. |

---

## 5. Database Schema (13 Tables)

- `users` (id, username, password, role)
- `classrooms` (id, name, created_by, invite_code, requires_usn, min_team_size, max_team_size, start_date, end_date)
- `classroom_members` (classroom_id, user_id, role, usn)
- `projects` (id, classroom_id, name, description, created_by, mentor_id)
- `project_members` (project_id, user_id, is_leader, join_status)
- `tasks` (id, project_id, assigned_to, title, description, status, milestone, priority, due_date)
- `issues`, `deliverables`, `activity_log`
- `weekly_submissions`, `weekly_submission_files`, `weekly_reviews`, `weekly_attendance` (Weekly Mentor Review System)

---

## 6. Previous Sessions Summary

The codebase has evolved significantly over recent sessions. Key milestones achieved include:

- **Security Hardening**: Resolved RCE vulnerabilities (file extension whitelists, `.htaccess`), DOM-XSS (via `escapeHTML()`), and addressed multiple IDOR and privilege escalation issues (e.g., verifying `created_by` in mentor assignment workflows).
- **Mentor Workflow & Weekly Reviews**: Added the ability for Coordinators to invite faculty as Admins (Mentors) and assign them to specific projects. Implemented a robust weekly log submission process for Project Leaders, accompanied by a mentor review modal for grading, remarks, and attendance tracking.
- **Dark Mode Fix & Real-Time Sync**: Fixed Tailwind CDN script ordering in `views/header.php`, emitted `themeToggled` custom event, and updated `assets/js/dashboard.js` to dynamically redraw Chart.js with active CSS variables (`--accent`, `--panel`, `--muted`). Removed hardcoded black/white modal classes in favor of `bg-overlay-subtle` and `hover:text-accent`.
- **Pure `docxtpl` VTU CS Report Generator & Scaffolding**: Refactored `code snippet/generate_report.py` to a pure `docxtpl` templating model conforming strictly to VTU B.E. CS report guidelines. The Python script never authors the academic content; it strictly binds live project and student metadata (names, USNs, guide, coordinator, HOD, academic year) into `templates/report_template.docx`. Chapters 1-6, Abstract, TOC, References, Photo Gallery, and Bio Data provide structured student placeholders and scaffolding. Implemented dual section page borders: Section 1 (Cover Page & Certificates) features a double border (`thickThinMediumGap`, outer thick, inner solid 1px line); Section 2 (Report Body) features a single border (clean 2pt black box), running header (`Mini-Project Report (21CSMP58)` | Department), and running footer (`project_name` | `Page X` dynamic Word field). Built standalone template generation support via `--build-template`.
- **Robust Schema & Data Integrity**: Consolidated all DB modifications into a single `schema.sql`. Added strict index constraints, handled missing `created_at` timestamps, and introduced date validation (e.g., `start_date` vs `end_date` checks). Created a Python data seed generator (`generate_seed.py`) to map real VTU Excel student groups to test accounts.
- **Architectural Cleanup**: Entirely stripped out legacy "Demo Mode" code that bypassed the database, relying fully on live data. Fixed deprecation warnings (e.g. `htmlspecialchars(null)`) and view structure mismatch errors.

---

## 7. Design Decisions

### Mentor Model
- Mentors are **Admin-role users** in `classroom_members`, same as the coordinator.
- The classroom **creator** (`classrooms.created_by`) is the **Coordinator** — sees all groups, assigns mentors, invites faculty.
- Other Admins are **Mentors** — see only projects where `projects.mentor_id = their_id`.

### Weekly Review & Attendance Flow
1. Coordinator sets `start_date` and `end_date` -> system computes weeks.
2. **Leader submits** weekly log (summary, next steps, file uploads).
3. **Mentor reviews**: sets status (approved/revision_needed), writes remarks, registers Saturday meeting attendance.
4. "Revision needed" allows students to update and re-submit the log using "Submit Revision". Empty weeks can be evaluated by mentors even if no log was uploaded.

---

## 8. What's Not Built Yet

- **Coordinator's weekly progress dashboard**: The heat-map overview table showing all groups × all weeks with status badges (discussed in design, not implemented).
- **Classroom archiving**: After `end_date + 1 day grace`, students get read-only access, teachers keep edit access.
- **`lg:` responsive prefixes**: Still missing from `teacher.php` ledger grid and some other grids.

---

## 9. DB Migration for Existing XAMPP Databases
*Run in phpMyAdmin for databases created prior to the Weekly Reviews update (see `schema.sql` for full schema).*

---

## 10. Reference: The Real-World Data

The `MIN PROJECT A-B Section.xlsx` spreadsheet in the repo maps real VTU student groups:
- 13 groups, ~50 students across sections A and B
- 7 distinct faculty guides
- One student (ANRAJ) has "Not submitted" with 0 marks — the unassigned edge case
- USN format: `1RG24CS015` (VTU standard)
