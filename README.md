# PMS — Academic Project Management Platform

PMS is an academic project management web platform built in native PHP 8.x, MySQL/MariaDB, Tailwind CSS, and Vanilla JavaScript, tailored specifically for engineering college project curriculums (including VTU).

## Key Features

- **Classroom Isolation & Contextual Roles**: Workspaces organized by class section with contextual permissions (Admin/Teacher, Project Leader, Team Member).
- **Kanban & Milestone Engine**: Drag-and-drop Kanban board with auto-derived 1-week phases (`1 phase = 1 week`), week rollups, and calendar view.
- **Weekly Progress Logs & Attendance**: Leaders submit weekly summaries, deliverables, and student attendance; mentors review, approve, or request revisions.
- **Escalation Flare & Blocker Management**: Team members can raise blockers with 4 severity levels (`low`, `medium`, `high`, `critical`), notifying mentors and leaders with one-click resolution.
- **VTU Project Report Assistant**: Interactive VTU compliance checklist and dynamic `.docx` template generator with pre-filled cover page, certificate, declaration, and 6 standard chapters.
- **Real-Time Notification Bell**: Bell dropdown with unread badge alerting users of join requests, invitations, task assignments, reviews, and escalations.
- **Dynamic Activity Heatmap**: 10-week contribution heatmap calculated dynamically from the project's audit activity log.
- **Team Marketplace**: Students can browse projects, submit join requests, withdraw pending requests, or accept/decline team invitations from leaders.

## How to Set Up and Run

1. **Prerequisites**:
   - Install [XAMPP](https://www.apachefriends.org/) with Apache and MySQL / MariaDB (PHP 8.x).
   - Start both **Apache** and **MySQL** from the XAMPP Control Panel.

2. **Placement**:
   - Clone or copy this repository into your XAMPP web root:
     `C:\xampp\htdocs`

3. **Database Setup**:
   - Open phpMyAdmin: `http://localhost/phpmyadmin/`
   - Import `schema.sql` to create the `pms` database and all 14 tables.
   - *(Optional for evaluation/testing)*: Import `demo_seed.sql` to populate sample classrooms, projects, tasks, and weekly logs.
   - Check connection settings in `dbs.php` (default: host `localhost`, user `root`, password ``, database `pms`).

4. **Running the Application**:
   - Open `http://localhost/mini-project-pms/login.php` or `http://localhost/mini-project-pms/registers.php`.
   - Log in or register an account, and navigate classrooms through the central portal (`hub.php`).
