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

### Phase 1: Cut (Outside-In Removal)
1. **Views**: Remove Kanban board, calendar grid, contribution tracker, heatmap canvas, audit log panel, escalation modal, deliverables tab, and their includes in `leader.php` and `student.php`.
2. **Endpoints**: Delete `add_task.php`, `update_task_status.php`, `raise_issue.php`, `resolve_issue.php`, and `upload_deliverable.php`.
3. **Cleanup**: Remove unused repository functions, task/issue notification triggers, and legacy front-end JS.
4. **Test**: Verify all dashboard views across all roles with PHP error reporting enabled. Keep old tables untouched for now.

### Phase 2: Weekly Meeting Schema
Derive Saturday-aligned weekly meetings from classroom `start_date` and `end_date` without depending on tasks or phases.
- `weekly_meetings`: `id`, `project_id`, `week_number`, `meeting_date`, `status` (`scheduled`, `held`, `rescheduled`, `holiday`), `team_update` (work done, next steps, blockers), `guide_feedback`, `submitted_by`, `submitted_at`. `UNIQUE(project_id, week_number)`.
- `meeting_attendance`: `meeting_id`, `user_id`, `status` (`present`, `absent`, `excused`), `marked_by`, `marked_at`, `PRIMARY KEY (meeting_id, user_id)`.
- `attendance_changes`: `id`, `meeting_id`, `user_id`, `old_status`, `new_status`, `changed_by`, `reason`, `changed_at` (append-only log).
- `guide_instructions`: `id`, `meeting_id`, `text`, `status` (`open`, `acknowledged`, `done`), `created_by`, `closed_at`.
- `weekly_submission_files`: Link attachments to `meeting_id` with `original_name`, `stored_name`, `file_path`, `file_size`, `mime_type`.

### Phase 3: Guide-Controlled Attendance
1. Fast-entry attendance UI for guides: mark present, absent, or excused for all team members in ~10 seconds.
2. Server-side authorization check: user must be project mentor or classroom Admin.
3. Post-save edits require a non-empty reason and insert into `attendance_changes`.
4. Allow setting meeting status to `rescheduled` or `holiday` (no absences counted).
5. Display personal attendance percentage on student dashboards.

### Phase 4: Weekly Updates & Actionable Instructions
1. Team writes weekly update (`work done`, `next steps`, `blockers`) and uploads files. Any active member can submit; captures `submitted_by`.
2. Guide inputs feedback and adds specific action items into `guide_instructions`.
3. Rollover: Next week's view displays prior open instructions at the top. Students acknowledge items; guide marks them done.
4. Append-only guide feedback history across meetings.
5. Secure file uploads: MIME inspection, size limits, generated filenames.

### Phase 5: Milestones & Team Rules
1. `classroom_milestones`: `id`, `classroom_id`, `title`, `due_date` set by coordinator. Display milestone list and countdown on all dashboards.
2. Team size guard: Enforce maximum of 4 active members (and minimum of 1) server-side across join, accept, and invite endpoints.
3. Teacher Ledger rework: Health computed strictly from meetings held, student attendance, and open instruction count (zero task dependencies).

### Phase 6: Evaluation Marks Sheet
1. `project_marks`: `project_id`, `report_marks` (out of 50). Stored once per project (shared by batchmates).
2. `student_marks`: `project_id`, `user_id`, `presentation_marks` (out of 25), `qa_marks` (out of 25). Total = Report + Presentation + Q&A.
3. Evaluator access only (guide and classroom Admins).
4. Finalize lock: Marking finalized prevents edits; subsequent updates restricted to Admin and logged.
5. Display attendance summary alongside marks. Export printable marks and attendance sheet (CSV/printable HTML/PDF).

### Phase 7: Report Assistant, Documentation & Final Cleanup
1. Swappable VTU Word report template pre-filled with project and weekly meeting data.
2. Run database migration dropping deprecated tables (`tasks`, `deliverables`, `issues`, `activity_log`, legacy tables).
3. Update `schema.sql` and `demo_seed.sql` to represent the final meeting/attendance/marks schema cleanly.
4. Align docs: update `README.md`, sync `AGENTS.md` and `GEMINI.md`, and clean up any incorrect architectural claims.
