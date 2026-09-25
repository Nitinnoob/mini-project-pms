# GEMINI.md — Project Documentation & AI Context for PMS (SyncSpace)

## 1. Project Overview

**PMS (Project Management System)**, internally codenamed **SyncSpace**, is an academic project management web platform built specifically for college/university environments (tailored for VTU — Visvesvaraya Technological University engineering curriculums).

### Core Problem Solved
In undergraduate engineering courses, students must complete semester mini-projects in teams, while faculty guides/professors (HODs, project coordinators, guides) must oversee dozens of teams across sections. PMS provides:
- **Classroom isolation**: Workspaces organized by class/section (e.g. *5th Sem CS Mini-Projects*).
- **Project Marketplace**: Students without a group can browse available projects in their classroom and request to join, or create their own project team and become Project Leader.
- **Kanban & Task Engine**: Interactive Kanban board (Todo, In Progress, Done) with drag-and-drop state transitions, task assignment, and priority flagging.
- **USN & Accountability**: Enforces official University Seat Numbers (USNs) formatted for VTU (e.g., `1RG24CS015`) for official classrooms.
- **Role-tailored UI & Visual Themes**: Dynamic CSS design tokens switching typography, palettes, and layouts between three personas: **Student (Workbench)**, **Project Leader (Control Room)**, and **Teacher (Marking Desk)**.
- **Presentation / Demo Mode**: Built-in mock mode allowing presentations and UI walkthroughs without live database dependencies.

---

## 2. Technology Stack & Dependencies

| Layer | Technologies / Libraries |
| :--- | :--- |
| **Backend** | Native PHP 8.x (Procedural + PDO prepared statements, session management) |
| **Database** | MySQL / MariaDB (via PDO with `utf8mb4_unicode_ci` and `utf8mb4_bin` collations) |
| **Server Runtime** | Apache / XAMPP (`htdocs/pjtmgmt2`) |
| **CSS Framework** | [Tailwind CSS CDN](https://cdn.tailwindcss.com) + custom [syncspace.css](file:///C:/xampp/htdocs/pjtmgmt2/syncspace.css) token system |
| **Icons & Fonts** | FontAwesome 6.4.0, Google Fonts (*IBM Plex Sans*, *IBM Plex Sans Condensed*, *IBM Plex Mono*, *IBM Plex Serif*) |
| **JavaScript UI** | [SortableJS](https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js) (Kanban drag-and-drop), [Chart.js](https://cdn.jsdelivr.net/npm/chart.js) (doughnut contribution charts), Vanilla Fetch API |

---

## 3. Repository Structure & File Map

```
C:\xampp\htdocs\pjtmgmt2\
├── dbs.php                  # Database connection singleton (PDO MySQL)
├── schema.sql               # MySQL DDL table schemas, foreign keys, and indexes
├── header.php               # Shared HTML <head>, font/CDN links, loading overlay, and auth styles
├── login.php                # User login form with password_verify and session initiation
├── registers.php            # User registration form with password_hash (role set to NULL)
├── logout.php               # Session destruction and cookie invalidation
├── hub.php                  # Central portal: lists user's classrooms, join/create actions, & UI demo cards
├── create_classroom.php     # Form for creating a classroom, generating 6-char invite code, assigning Admin role
├── join.php                 # Form to join a classroom via invite code; validates VTU USN if required
├── create_subproject.php    # Form for a student to create a new project in a classroom and become Leader
├── dashboard.php            # Master controller & UI for Student, Project Leader, Teacher, and Marketplace views
├── teacher_group_view.php   # Detailed drill-down report for teachers on a specific student group
├── add_task.php             # Endpoint to create a task assigned to a project and member
├── update_task_status.php   # JSON AJAX endpoint for Kanban card drag-and-drop status changes
├── request_join.php         # Endpoint for a student to submit a 'Pending' join request to a project
├── manage_join_request.php  # Endpoint for project leaders to accept pending join requests
├── syncspace.css            # Multi-theme CSS design token definitions ([data-mode="student|leader|teacher"])
├── refactor.php             # Deprecated / broken utility script (syntax corruption from macro replacement)
└── README.md                # Basic repo introduction and quick setup instructions
```

---

## 4. Database Schema & Architecture

The database `pms` (`utf8mb4`) consists of 7 normalized tables defined in [schema.sql](file:///C:/xampp/htdocs/pjtmgmt2/schema.sql):

### 1. `users`
- `id` (INT PK, AUTO_INCREMENT)
- `username` (VARCHAR(100), UNIQUE)
- `password` (VARCHAR(255), bcrypt hashed)
- `role` (VARCHAR(50), DEFAULT NULL) — *Legacy column kept NULL; roles are now contextual to classrooms.*

### 2. `classrooms`
- `id` (INT PK, AUTO_INCREMENT)
- `name` (VARCHAR(255))
- `created_by` (INT FK -> `users.id`)
- `invite_code` (VARCHAR(10), UNIQUE, case-sensitive `utf8mb4_bin` collation)
- `requires_usn` (TINYINT(1), DEFAULT 0) — Flag determining if joining students must supply a VTU USN.

### 3. `classroom_members`
- `classroom_id` (INT FK -> `classrooms.id`)
- `user_id` (INT FK -> `users.id`)
- `role` (VARCHAR(50)) — Contextual role: `'Admin'` (created the classroom / Teacher) or `'Team Member'` (Student).
- `usn` (VARCHAR(20), DEFAULT NULL) — Student USN scoped to this classroom.
- Composite PK: `(classroom_id, user_id)`
- Unique Constraint: `(classroom_id, usn)` to prevent duplicate USNs in the same classroom.

### 4. `projects`
- Subprojects / teams within a classroom.
- `id` (INT PK, AUTO_INCREMENT)
- `classroom_id` (INT FK -> `classrooms.id`)
- `name` (VARCHAR(255))
- `description` (TEXT)
- `created_by` (INT FK -> `users.id`)

### 5. `project_members`
- Links students to project groups with membership states.
- `project_id` (INT FK -> `projects.id`)
- `user_id` (INT FK -> `users.id`)
- `is_leader` (TINYINT(1), DEFAULT 0) — 1 if the user created or leads the project.
- `join_status` (VARCHAR(50), DEFAULT `'Active'`) — `'Pending'` (applied via Marketplace) or `'Active'` (accepted / creator).
- Composite PK: `(project_id, user_id)`

### 6. `tasks`
- Kanban task units.
- `id` (INT PK, AUTO_INCREMENT)
- `project_id` (INT FK -> `projects.id`)
- `assigned_to` (INT FK -> `users.id`, ON DELETE SET NULL)
- `title` (VARCHAR(255))
- `description` (TEXT)
- `status` (ENUM: `'todo'`, `'inprogress'`, `'done'`, DEFAULT `'todo'`)
- `priority` (ENUM: `'normal'`, `'high'`, DEFAULT `'normal'`)
- `due_date` (DATE)
- `created_at` (TIMESTAMP)

### 7. `issues`
- Project blockers and flags.
- `id` (INT PK, AUTO_INCREMENT)
- `project_id` (INT FK -> `projects.id`)
- `raised_by` (INT FK -> `users.id`)
- `description` (TEXT)
- `severity` (ENUM: `'amber'`, `'red'`, DEFAULT `'amber'`)
- `status` (ENUM: `'open'`, `'resolved'`, DEFAULT `'open'`)
- `created_at` (TIMESTAMP)

---

## 5. Architectural Roles & UI Themes

The system replaces traditional single-role authentication with **contextual roles**:
A single user account can be an **Admin (Teacher)** in their own classroom, while simultaneously being a **Student** or **Project Leader** in another classroom.

In [syncspace.css](file:///C:/xampp/htdocs/pjtmgmt2/syncspace.css), themes are switched via the `data-mode` attribute on `<html>` or `<body>`:

| Persona | CSS Mode Attribute | Visual Theme & Mood | Typography | Key Screens |
| :--- | :--- | :--- | :--- | :--- |
| **Student** | `data-mode="student"` | Dark slate (`#12151d`), warm amber & cyan accents, 8px radius | *IBM Plex Sans* | Kanban Workbench, Calendar View, Personal Progress |
| **Project Leader** | `data-mode="leader"` | Deep cockpit navy (`#080b14`), emerald & amber accents, 4px radius | *IBM Plex Sans Condensed* | Kanban Board + Team Management tab (Approve Requests, Roster, Member invites) |
| **Teacher** | `data-mode="teacher"` | Light parchment (`#e4e2d6`), forest green & crimson accents, 2px radius | *IBM Plex Serif* | Groups Ledger, Completion Progress bars, Group Detail Reports, Classroom Roster |
| **Unassigned Student** | `data-mode="student"` | Slate palette | *IBM Plex Sans* | Marketplace: list of projects, request-to-join buttons, or "Start a Project" |

---

## 6. Demo / Presentation Mode

To facilitate project vivas, presentations, and UI evaluations without requiring a fully seeded MySQL database, [hub.php](file:///C:/xampp/htdocs/pjtmgmt2/hub.php) and [dashboard.php](file:///C:/xampp/htdocs/pjtmgmt2/dashboard.php) support a zero-dependency **Demo Mode**:

- Triggered via query parameters:
  - Student Demo: `dashboard.php?classroom_id=demo&demo_view=Student`
  - Leader Demo: `dashboard.php?classroom_id=demo&demo_view=Project+Leader`
  - Teacher Demo: `dashboard.php?classroom_id=demo&demo_view=Teacher`
  - Marketplace Demo: `dashboard.php?classroom_id=demo&demo_view=Marketplace`
- When active, `dashboard.php` skips database queries and populates simulated tasks, teams, calendar items, and mock groups (e.g., *Group 23 — Project management system*, *Group 14 — Library Management system*).

---

## 7. Known Issues, Technical Debt & Quirks

1. **`add_task.php` Missing Classroom Parameter**:
   - `add_task.php` currently redirects to `Location: dashboard.php` without `classroom_id`. Because `dashboard.php` requires `classroom_id`, the user gets bounced back to `hub.php`. It should accept and pass `classroom_id` (or referer).
2. **Corrupted File `refactor.php`**:
   - Contains invalid PHP syntax where variables are replaced with `\`. This was an automated script artifact and should either be fixed or deleted.
3. **Legacy Oracle References**:
   - `README.md` states *"install any oracle database"* and some comments in `login.php` / `registers.php` mention Oracle column formats. The active codebase is 100% MySQL / MariaDB PDO.
4. **Hardcoded Data in `teacher_group_view.php`**:
   - `teacher_group_view.php` uses a static `$allGroups` array (Groups 23, 14, 16, 15, 17, 18) rather than querying `projects`, `tasks`, and `issues` from MySQL.
5. **Join Request Rejection**:
   - `manage_join_request.php` only supports `$action === 'accept'`. There is currently no option for leaders to reject or dismiss a pending request.
6. **Teacher Group View Authentication**:
   - `teacher_group_view.php` checks `$_SESSION['role']`, but `login.php` no longer writes `role` into `$_SESSION`. It should verify the user's role from `classroom_members` for that classroom.

---

## 8. Local Setup & Execution Guide

1. **Prerequisites**:
   - XAMPP installed with Apache and MySQL services running.
2. **Installation**:
   - Place this directory in `C:\xampp\htdocs\pjtmgmt2` (or symlink).
3. **Database Configuration**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
   - Run the SQL script [schema.sql](file:///C:/xampp/htdocs/pjtmgmt2/schema.sql) to create database `pms` and all tables.
   - Confirm credentials in [dbs.php](file:///C:/xampp/htdocs/pjtmgmt2/dbs.php) (`$db_host = 'localhost'`, `$db_name = 'pms'`, `$db_user = 'root'`, `$db_pass = ''`).
4. **Accessing the App**:
   - Open `http://localhost/pjtmgmt2/login.php` or `http://localhost/pjtmgmt2/registers.php`.
   - Register an account, log in, and proceed to the Hub (`hub.php`).
