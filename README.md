# PMS: Academic Project Review & Continuous Evaluation System

A modern, authentic academic project review, media showcase, and continuous internal evaluation (CIE) platform built for engineering colleges and autonomous universities.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-PDO-4479A1?style=flat&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-CDN-38B2AC?style=flat&logo=tailwind-css&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-Native%20Zero--Build-indigo)

---

## Key Features

- **Role-Based Access Control (RBAC)**: Clean Student (with mandatory USN) vs Teacher (with mandatory Staff ID) identities.
- **Multi-Institution Classroom Isolation**: Teachers create classroom sections generating clash-proof 6-character invite codes (e.g., `RV-CS6B`).
- **Saturday Guide Review Engine**: Derived week-by-week meeting schedule with **strict temporal locking**:
  - *Future Weeks*: Locked against advance student submissions and teacher attendance.
  - *Current Active Week*: Open for 3-part progress logs (`work_done`, `next_steps`, `blockers`), guide remarks, and batch attendance.
  - *Past / Concluded Weeks*: Frozen read-only archives; modifications require mandatory audit justification logged in `attendance_changes`.
- **Actionable Mentor Directives**: Review action items with automated rollover lifecycle (`open` &rarr; `acknowledged` &rarr; `done`).
- **Guide Attendance & 75% Shortage Flag**: 10-second batch attendance with prominent warning flags for students falling below university 75% thresholds.
- **Project Deliverables & Media Showcase CMS**: Visual media library for screenshots, hardware prototype photos, GitHub repositories, and demo videos with faculty exhibition spotlight pinning.
- **Continuous Internal Evaluation (CIE) Marks Engine**:
  - **50 Marks**: Project Report & Documentation (team score).
  - **25 Marks**: Presentation & Technical Demo (individual score).
  - **25 Marks**: Viva Voce & Technical Q&A (individual score).
  - **Finalize Lock**: Prevents accidental alterations; modifications require teacher justification logged in `marks_changes`.
- **Printable A4 Landscape Department Ledger & CSV Export**: Official marks sheet formatted for direct university marks entry with dynamic College and Department letterhead and sign-off blocks for Guide, Coordinator, and HOD.

---

## Local Setup (XAMPP)

### 1. Place in Web Root
Clone or copy this folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\oneshot
```

### 2. Database Import
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open phpMyAdmin at `http://localhost/phpmyadmin`.
3. Import `schema.sql` to create database `pms` and all tables.
4. Import `demo_seed.sql` to populate sample faculty, students, review schedule, assets, and CIE marks.

### 3. Open Application
Navigate to:
```
http://localhost/oneshot/
```

---

## Pre-Seeded Demo Accounts (Password: `password123`)

| Role | Username | Password | Full Name | ID / USN |
| :--- | :--- | :--- | :--- | :--- |
| **Teacher (Coordinator)** | `roopa` | `password123` | Prof. Roopa M | `CS-FAC-101` |
| **Student (Team Leader)** | `keerthana` | `password123` | Keerthana R | `1MS21CS042` |
| **Student (Teammate)** | `lokesh` | `password123` | Lokesh N | `1MS21CS048` |
| **Student (Teammate)** | `adhitya` | `password123` | Adhitya G | `1MS21CS012` |

- **Sample Classroom Section**: `7th Sem Major Project (Sec A)`
- **Classroom Invite Code**: `RV-CS6B`
- **Institution**: RV College of Engineering, Bengaluru

---

## File Structure

```
oneshot/
├── config/
│   └── evaluation.php               # CIE marks weights (50/25/25) & attendance rule
├── dbs.php                          # Clean PDO MySQL connection
├── auth_guard.php                   # Role checks (is_teacher(), is_student()) & session helper
├── login.php & logout.php           # Authentication entrypoints with 1-click test buttons
├── registers.php                    # Student (USN) / Teacher (Staff ID) registration
├── hub.php                          # Classroom overview & join by 6-char invite code
├── create_classroom.php             # Teacher creates classroom with institution letterhead & code
├── create_project.php               # Student creates team (1-4) & assigns guide
├── dashboard.php                    # Role-based workspace router
│
├── meeting_engine.php               # Saturday review derivation & temporal week locks
├── save_meeting_attendance.php      # 1-Click batch attendance & audit logger
├── submit_weekly_log.php            # Saturday progress submission (work/next/blockers)
├── manage_instruction.php           # Actionable mentor directives & rollover lifecycle
│
├── upload_project_asset.php         # Student asset uploader (media/code links)
├── manage_project_asset.php         # Teacher media curation & exhibition spotlight
│
├── save_marks.php                   # CIE 50/25/25 scoring, validation & finalize lock
├── export_marks.php                 # Printable A4 landscape ledger & CSV export
├── schema.sql                       # Clean MySQL schema
├── demo_seed.sql                    # Realistic demo data aligned with current semester
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
