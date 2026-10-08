# Implementation Plan: Lean Academic Weekly Meeting Engine

## 1. Goal & Architectural Pivot
Transform PMS from a Kanban/task tracker into a lean, guide-centered academic project review engine where the **Saturday guide meeting** is the core operational record. Continuous Internal Evaluation (CIE) and viva preparation are driven by weekly guide meetings, strict attendance logs, actionable mentor instructions, and evaluation marks.

---

## 2. Invariants & Guardrails
1. **Guide-Controlled Attendance**: Only the assigned guide or classroom Admin can mark/edit attendance (verified server-side against project records). Students and leaders cannot alter attendance.
2. **Attendance Audit Trail**: Any post-meeting attendance modification requires a mandatory reason and is logged immutably in `attendance_changes`.
3. **No Premature Deletions**: In Phase 1, cut views, endpoints, and repo references. Stop writing to old tables, but do not drop database tables until Phase 7.
4. **File Upload Security**: Uploads require strict MIME/extension validation, size checks, generated unguessable stored filenames, and secure non-executable storage.
5. **Config-Driven Evaluation**: Assessment weights (50 Report / 25 Presentation / 25 Q&A) reside in configuration, never hardcoded.

---

## 3. Coordinator Confirmations Needed (**Confirm**)
1. Will guides mark attendance in the app directly during/after meetings?
2. Is there a standard department attendance or marks sheet template to export?
3. For marks entry: does the department require one consolidated mark or independent marks per evaluator?
4. What is the official attendance-shortage marks deduction policy (if any)?

---

## 4. Phase-by-Phase Implementation

### Phase 0: Prep [COMPLETED]
> **Status: Completed** — Branch `lean-meeting-engine` & tag `pre-lean-pivot` created, DB backed up, rules synced, questions confirmed, removal targets mapped.
1. Create git branch and tag; back up current database.
2. Update `AGENTS.md`: Lift `demo_seed.sql` edit restriction; enforce "SQL only in repositories and endpoints, never in views".
3. Ask coordinator the confirmation questions above.
4. Grep codebase for `task`, `audit`, `heatmap`, `contribution`, `issue`, `escalat`, `deliverable` to map all files for removal.

### Phase 1: Cut (Outside-In Removal) [COMPLETED]
> **Status: Completed** — Removed Kanban board, calendar grid, contribution tracker, heatmap canvas, audit log panel, escalation modal, deliverables tab, task/issue endpoints (`add_task.php`, `update_task_status.php`, `raise_issue.php`, `resolve_issue.php`, `upload_deliverable.php`), dead repositories, notification hooks, and legacy front-end JS. Verified dashboard views across roles and ensured all tests pass.
1. **Views**: Remove Kanban board, calendar grid, contribution tracker, heatmap canvas, audit log panel, escalation modal, deliverables tab, and their includes in `leader.php` and `student.php`.
2. **Endpoints**: Delete `add_task.php`, `update_task_status.php`, `raise_issue.php`, `resolve_issue.php`, and `upload_deliverable.php`.
3. **Cleanup**: Remove unused repository functions, task/issue notification triggers, and legacy front-end JS.
4. **Test**: Verify all dashboard views across all roles with PHP error reporting enabled. Keep old tables untouched for now.

### Phase 2: Weekly Meeting Schema [COMPLETED]
> **Status: Completed** — Saturday-aligned weekly meetings derived from classroom `start_date` and `end_date` without depending on tasks or phases (`meeting_engine.php`). Full database schema defined in `schema.sql` and applied to live `pms` database with seed support in `demo_seed.sql`. Implemented `repositories/meeting_repository.php` with full CRUD, guide attendance, immutable `attendance_changes` audit log, `guide_instructions` lifecycle, and `weekly_submission_files` metadata attachments. Verified with 47 automated tests in `tests/run.php`.
Derive Saturday-aligned weekly meetings from classroom `start_date` and `end_date` without depending on tasks or phases.
- `weekly_meetings`: `id`, `project_id`, `week_number`, `meeting_date`, `status` (`scheduled`, `held`, `rescheduled`, `holiday`), `team_update` (work done, next steps, blockers), `guide_feedback`, `submitted_by`, `submitted_at`. `UNIQUE(project_id, week_number)`.
- `meeting_attendance`: `meeting_id`, `user_id`, `status` (`present`, `absent`, `excused`), `marked_by`, `marked_at`, `PRIMARY KEY (meeting_id, user_id)`.
- `attendance_changes`: `id`, `meeting_id`, `user_id`, `old_status`, `new_status`, `changed_by`, `reason`, `changed_at` (append-only log).
- `guide_instructions`: `id`, `meeting_id`, `text`, `status` (`open`, `acknowledged`, `done`), `created_by`, `closed_at`.
- `weekly_submission_files`: Link attachments to `meeting_id` with `original_name`, `stored_name`, `file_path`, `file_size`, `mime_type`.

### Phase 3: Guide-Controlled Attendance [COMPLETED]
> **Status: Completed** — Built fast-entry modal UI for guides with 1-click batch marking (`views/attendance_modal.php`), server-side authorization checking mentor/Admin access (`can_manage_project_attendance` in `auth_guard.php`), mandatory modification reasons with immutable logging to `attendance_changes`, `rescheduled` and `holiday` meeting status handling with zero absence penalties, and personal attendance percentage display with university shortage alert (<75%) on student and leader dashboards. Verified with 52 automated tests in `tests/run.php`.
1. Fast-entry attendance UI for guides: mark present, absent, or excused for all team members in ~10 seconds.
2. Server-side authorization check: user must be project mentor or classroom Admin.
3. Post-save edits require a non-empty reason and insert into `attendance_changes`.
4. Allow setting meeting status to `rescheduled` or `holiday` (no absences counted).
5. Display personal attendance percentage on student dashboards.

### Phase 4: Weekly Updates & Actionable Instructions [COMPLETED]
> **Status: Completed** — Implemented comprehensive weekly update system (`work done`, `next steps`, `blockers`) submittable by any active team member with `submitted_by` user attribution (`submit_weekly_log.php`, `views/weekly_log_modal.php`). Built guide feedback & directive input with specific action items (`guide_instructions`) and append-only audit trail (`meeting_feedback_history`, `submit_mentor_review.php`, `views/mentor_review_modal.php`). Implemented action items rollover panel displaying prior open instructions across weeks, with student acknowledgement and guide closure (`manage_instruction.php`, `views/partials/weekly_logs.php`). Added secure file upload handling with strict MIME inspection via `finfo`, extension whitelist, 10MB size limits, unguessable generated filenames, and execution-disabled storage (`uploads/meetings/`). Verified with 58 automated tests in `tests/run.php`.
1. Team writes weekly update (`work done`, `next steps`, `blockers`) and uploads files. Any active member can submit; captures `submitted_by`.
2. Guide inputs feedback and adds specific action items into `guide_instructions`.
3. Rollover: Next week's view displays prior open instructions at the top. Students acknowledge items; guide marks them done.
4. Append-only guide feedback history across meetings.
5. Secure file uploads: MIME inspection, size limits, generated filenames.

### Phase 5: Milestones & Team Rules [COMPLETED]
> **Status: Completed** — Defined `classroom_milestones` schema (`schema.sql`, live MySQL, `demo_seed.sql`) and built repository (`repositories/milestone_repository.php`) with countdown calculation (upcoming, due today, passed). Added coordinator management endpoint (`manage_milestone.php`) with tab UI in Teacher view and milestone countdown banner (`views/partials/milestone_widget.php`) across Teacher, Project Leader, and Student dashboards. Enforced team size guard (maximum of 4 active members and minimum of 1) server-side across join requests (`request_join.php`), accept requests (`manage_join_request.php`), invitations (`handle_invitation.php`), and classroom configuration (`create_classroom.php`, `controllers/dashboard_context.php`, `repositories/membership_repository.php`). Completely reworked Teacher Ledger in `controllers/teacher_dashboard.php` and `views/teacher.php` to derive project health strictly from meetings held, student attendance, and open instruction count with zero task dependencies. Verified with 61 automated tests in `tests/run.php`.
1. `classroom_milestones`: `id`, `classroom_id`, `title`, `due_date` set by coordinator. Display milestone list and countdown on all dashboards.
2. Team size guard: Enforce maximum of 4 active members (and minimum of 1) server-side across join, accept, and invite endpoints.
3. Teacher Ledger rework: Health computed strictly from meetings held, student attendance, and open instruction count (zero task dependencies).

### Phase 6: Evaluation Marks Sheet [COMPLETED]
> **Status: Completed** — Defined config-driven Continuous Internal Evaluation (CIE) architecture (`config/evaluation.php`) supporting 50 Report / 25 Presentation / 25 Viva Q&A (Total 100). Implemented `project_marks`, `student_marks`, and `marks_changes` schema tables (`schema.sql`, live MySQL, `demo_seed.sql`). Created `repositories/marks_repository.php` with full CRUD, range validations, student attendance percentage correlation, evaluator authorization checks (`can_evaluate_project`), finalize lock enforcement, and Admin-only modification audits with mandatory reason logging. Built evaluator fast-entry modal (`views/marks_modal.php`) with dynamic live totals, dedicated Evaluation Marks tab in Teacher view (`views/teacher.php`), and student evaluation cards on Student & Leader dashboards (`views/student.php`, `views/leader.php`). Implemented export endpoint (`export_marks.php`) supporting CSV spreadsheet downloads and printable official department marks ledger HTML sheets. Verified with 68 automated tests in `tests/run.php`.
1. `project_marks`: `project_id`, `report_marks` (out of 50). Stored once per project (shared by batchmates).
2. `student_marks`: `project_id`, `user_id`, `presentation_marks` (out of 25), `qa_marks` (out of 25). Total = Report + Presentation + Q&A.
3. Evaluator access only (guide and classroom Admins).
4. Finalize lock: Marking finalized prevents edits; subsequent updates restricted to Admin and logged.
5. Display attendance summary alongside marks. Export printable marks and attendance sheet (CSV/printable HTML/PDF).

### Phase 7: Report Assistant, Documentation & Final Cleanup [COMPLETED]
> **Status: Completed** — Built the swappable VTU Word report assistant engine (`scripts/generate_report.py`, `generate_docx.php`, `config/report.php`, `templates/report_template.docx`) pre-filled with project details, team USNs & leader indicators, complete Saturday weekly guide review meeting records (`work_done`, `next_steps`, `blockers`, `guide_feedback`, `instructions`), Continuous Internal Evaluation (CIE) marks sheet (50 Report / 25 Pres. / 25 Q&A), and student/team attendance compliance metrics. Added "Generate Report Assistant" access modal for both Project Leaders and Students (`views/leader.php`, `views/student.php`, `views/report_assistant_modal.php`, `download_vtu_template.php`). Ran live MySQL database migration dropping deprecated tables (`tasks`, `deliverables`, `issues`, `activity_log`). Updated `schema.sql` and `demo_seed.sql` to represent the final meeting/attendance/marks schema cleanly with zero task dependencies, successfully tested with full demo seed re-import. Aligned documentation across `README.md`, `AGENTS.md`, and `GEMINI.md`. Verified with 71 automated tests in `tests/run.php`.
1. Swappable VTU Word report template pre-filled with project and weekly meeting data (`config/report.php`, `templates/report_template.docx`, `scripts/generate_report.py`, `generate_docx.php`, `download_vtu_template.php`).
2. Run database migration dropping deprecated tables (`tasks`, `deliverables`, `issues`, `activity_log`).
3. Update `schema.sql` and `demo_seed.sql` to represent the final meeting/attendance/marks schema cleanly.
4. Align docs: update `README.md`, sync `AGENTS.md` and `GEMINI.md`, and clean up any incorrect architectural claims.
