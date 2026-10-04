# GEMINI.md — Project Documentation & AI Context for PMS

## 1. Project Overview

**PMS (Project Management System)** is an academic project management web platform built specifically for college/university environments (tailored for VTU — Visvesvaraya Technological University engineering curriculums).

### Core Problem Solved
In undergraduate engineering courses, students must complete semester mini-projects in teams, while faculty guides/professors (HODs, project coordinators, guides) must oversee dozens of teams across sections. PMS provides:
- **Classroom isolation**: Workspaces organized by class/section (e.g. *5th Sem CS Mini-Projects*).
- **Project Marketplace**: Students without a group can browse available projects in their classroom and request to join, or create their own project team and become Project Leader.
- **Kanban & Task Engine**: Interactive Kanban board (Todo, In Progress, Done) with drag-and-drop state transitions, task assignment, and VTU phase milestones (Synopsis, Phase 1, Phase 2, Final Demo).
- **USN & Accountability**: Enforces official University Seat Numbers (USNs) formatted for VTU (e.g., `1RG24CS015`) for official classrooms.
- **Role-tailored UI & Visual Themes**: Dynamic CSS design tokens switching typography, palettes, and layouts between three personas: **Student (Workbench)**, **Project Leader (Control Room)**, and **Teacher (Marking Desk)**.
- **Weekly Logs & Reviews**: Weekly progress log submissions with file attachments, student attendance tracking, and faculty mentor approvals.
- **Deliverables & Audit Trail**: Upload phase deliverables and track real-time project timeline events in the activity feed.

---

## 2. Technology Stack & Dependencies

| Layer | Technologies / Libraries |
| :--- | :--- |
| **Backend** | Native PHP 8.x (Procedural + PDO prepared statements, session management) |
| **Database** | MySQL / MariaDB (via PDO with `utf8mb4_unicode_ci` and `utf8mb4_bin` collations) |
| **Server Runtime** | Apache / XAMPP (`htdocs/pjtmgmt2`) |
| **CSS Framework** | Compiled Tailwind CSS CLI (`assets/css/tailwind.min.css`) + custom [pms.css](file:///C:/xampp/htdocs/pjtmgmt2/pms.css) token system |
| **Icons & Fonts** | FontAwesome 6.4.0, Google Fonts (*IBM Plex Sans*, *IBM Plex Sans Condensed*, *IBM Plex Mono*, *IBM Plex Serif*) |
| **JavaScript UI** | [SortableJS](https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js) (Kanban drag-and-drop), [Chart.js](https://cdn.jsdelivr.net/npm/chart.js) (doughnut contribution charts), Vanilla Fetch API |

---

## 3. Repository Structure & File Map

```
D:\1University\5thsem\3_Personal\pjtmgmt\
├── dbs.php                     # Database connection singleton (PDO MySQL)
├── schema.sql                  # MySQL DDL table schemas (13 normalized tables)
├── demo_seed.sql               # Seed data for presentations, testing, and viva evaluations
├── auth_header.php             # Dedicated authentication layout shell for login/register pages
├── login.php                   # User login form with password_verify and session initiation
├── registers.php               # User registration form with password_hash
├── logout.php                  # Session destruction and cookie invalidation
├── hub.php                     # Central portal: lists user's classrooms and join/create actions
├── create_classroom.php        # Form for creating a classroom with CSPRNG invite code
├── join.php                    # Form to join a classroom via invite code; validates VTU USN
├── create_subproject.php       # Form for a student to create a new project in a classroom
├── dashboard.php               # Master controller & UI orchestrator for Student, Leader, Teacher, and Marketplace views
├── add_task.php                # Endpoint to create a task assigned to a project and member
├── update_task_status.php      # JSON AJAX endpoint for Kanban card drag-and-drop status changes
├── request_join.php            # Endpoint for a student to submit a 'Pending' join request to a project
├── manage_join_request.php     # Endpoint for project leaders to accept or decline pending join requests
├── upload_deliverable.php      # Endpoint for uploading deliverables with secure file extension validation
├── submit_weekly_log.php       # Endpoint for weekly progress summaries, attachments, and attendance
├── submit_mentor_review.php    # Endpoint for mentors to review submissions and add remarks
├── add_mentor_to_classroom.php # Endpoint for classroom coordinators to invite faculty as Mentors
├── assign_mentor.php           # Endpoint to link an assigned mentor to a project group
├── pms.css                     # Multi-theme CSS design token definitions ([data-mode="student|leader|teacher"])
├── views/
│   ├── header.php              # Shared app navigation header and brand bar
│   ├── footer.php              # Global scripts initialization (SortableJS, theme listeners)
│   ├── student.php             # Student Workbench view (Kanban & Calendar)
│   ├── leader.php              # Project Leader view (Control Room, Roster, Invites, Weekly Logs)
│   ├── teacher.php             # Teacher Marking Desk (Project Groups Ledger & Live Classroom Roster)
│   ├── marketplace.php         # Marketplace for unassigned students to browse/join projects
│   ├── sidebar.php             # Analytics sidebar (Contribution chart, Heatmap, Audit Log, Deliverables)
│   ├── add_task_modal.php      # Modal dialog for task creation
│   ├── upload_modal.php        # Modal dialog for file deliverables
│   ├── weekly_log_modal.php    # Modal dialog for weekly progress logs
│   ├── mentor_review_modal.php # Modal dialog for faculty mentor grading & remarks
│   └── partials/
│       └── board.php           # Shared, deduplicated Kanban Board and Month Calendar view partial
└── README.md                   # Project setup instructions
```

---

## 4. Database Schema & Architecture

The database `pms` (`utf8mb4`) consists of 13 normalized tables defined in [schema.sql](file:///D:/1University/5thsem/3_Personal/pjtmgmt/schema.sql):

1. **`users`**: User credentials (`id`, `username`, `password`, `created_at`).
2. **`classrooms`**: Academic classes (`id`, `name`, `created_by`, `invite_code`, `requires_usn`, `min_team_size`, `max_team_size`, `start_date`, `end_date`).
3. **`classroom_members`**: Classroom membership with contextual role (`'Admin'` or `'Team Member'`) and student VTU `usn`.
4. **`projects`**: Teams within a classroom (`id`, `classroom_id`, `name`, `description`, `created_by`, `mentor_id`).
5. **`project_members`**: Links students to project groups (`project_id`, `user_id`, `is_leader`, `join_status`: `'Pending'` or `'Active'`).
6. **`tasks`**: Kanban tasks (`id`, `project_id`, `assigned_to`, `title`, `description`, `status`: `'todo'|'inprogress'|'done'`, `milestone`: `'Synopsis'|'Phase 1'|'Phase 2'|'Final Demo'`, `priority`, `due_date`).
7. **`issues`**: Blockers raised by team members (`id`, `project_id`, `raised_by`, `description`, `severity`, `status`).
8. **`deliverables`**: Project document uploads (`id`, `project_id`, `task_id`, `uploaded_by`, `file_name`, `file_path`, `uploaded_at`).
9. **`activity_log`**: Audit trail of project events (`id`, `project_id`, `user_id`, `action`, `details`, `created_at`).
10. **`weekly_submissions`**: Weekly milestone log summaries (`id`, `project_id`, `week_number`, `submitted_by`, `work_summary`, `next_steps`).
11. **`weekly_submission_files`**: Multi-file attachments for weekly submissions (`id`, `submission_id`, `file_name`, `file_path`).
12. **`weekly_reviews`**: Guide/Mentor reviews (`id`, `submission_id`, `reviewed_by`, `status`: `'pending'|'approved'|'revision_needed'`, `mentor_remarks`).
13. **`weekly_attendance`**: Weekly student presence tracking (`submission_id`, `user_id`, `present`).

---

## 5. Architectural Roles & UI Themes

The system replaces traditional single-role authentication with **contextual roles**:
A single user account can be an **Admin (Teacher)** in their own classroom, while simultaneously being a **Student** or **Project Leader** in another classroom.

In [pms.css](file:///D:/1University/5thsem/3_Personal/pjtmgmt/pms.css), themes are switched via the `data-mode` attribute on `<html>`:

| Persona | CSS Mode Attribute | Visual Theme & Mood | Typography | Key Screens |
| :--- | :--- | :--- | :--- | :--- |
| **Student** | `data-mode="student"` | Dark slate (`#12151d`), warm amber & cyan accents, 8px radius | *IBM Plex Sans* | Kanban Workbench, Calendar View, Personal Progress, Join Classroom |
| **Project Leader** | `data-mode="leader"` | Deep cockpit navy (`#080b14`), emerald & amber accents, 4px radius | *IBM Plex Sans Condensed* | Kanban Board, Team Management tab (Approve/Decline Requests, Roster, Member invites), Weekly Logs |
| **Teacher** | `data-mode="teacher"` | Light parchment (`#e4e2d6`), forest green & crimson accents, 2px radius | *IBM Plex Serif* | Groups Ledger, Milestone Progress bars, Group Detail Reports, Classroom Roster, Create Classroom |
| **Unassigned Student** | `data-mode="student"` | Slate palette | *IBM Plex Sans* | Marketplace: list of projects, request-to-join buttons, or "Start a Project" |

---

## 6. Local Setup & Execution Guide

1. **Prerequisites**:
   - XAMPP installed with Apache and MySQL services running.
2. **Installation**:
   - Place this directory in `C:\xampp\htdocs\pjtmgmt2` (or symlink).
3. **Database Configuration**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
   - Run the SQL script [schema.sql](file:///D:/1University/5thsem/3_Personal/pjtmgmt/schema.sql) to create database `pms` and all 13 tables.
   - (Optional) Run [demo_seed.sql](file:///D:/1University/5thsem/3_Personal/pjtmgmt/demo_seed.sql) to populate sample VTU classrooms, student groups, tasks, and weekly logs for vivas and demonstrations.
   - Confirm credentials in [dbs.php](file:///D:/1University/5thsem/3_Personal/pjtmgmt/dbs.php) (`$db_host = 'localhost'`, `$db_name = 'pms'`, `$db_user = 'root'`, `$db_pass = ''`).
4. **Accessing the App**:
   - Open `http://localhost/pjtmgmt2/login.php` or `http://localhost/pjtmgmt2/registers.php`.
   - Register an account, log in, and proceed to the Hub (`hub.php`).
