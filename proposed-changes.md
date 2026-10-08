Yes, I think it's a better direction, and the README shows why. Of the eight listed features, four exist only because tasks exist: the Kanban and milestone engine, the activity heatmap, the task-assignment notifications, and the calendar view. Cutting them leaves the weekly logs, review, escalation, report assistant, marketplace, and classroom roles, which is a coherent product. The README also says leaders already submit summaries, deliverables, and attendance, and mentors already approve or request revisions. So this is mostly subtraction, not a rebuild.

I'm working from the README and guardrails here, not the code, so treat the dependencies below as things to verify.

**Things the README reveals that change the plan**

1. **Phases are derived from tasks.** The README says "auto-derived 1-week phases (`1 phase = 1 week`)" under the Kanban engine. If weekly logs are the core, the week or phase list can't depend on tasks. The simplest source is the classroom's `start_date` and `end_date`: week N is start + N weeks. Rewire this first, because the teacher's phase-completion bars, the phase chips, and the log tagging all hang off it.
2. **Heatmap and contribution depend on the audit log.** The README says the heatmap comes from the project's audit activity log, and that log is mostly "moved task X" events. Once tasks go, it has nothing to record. Cut it all together, but check that nothing else writes to the audit log (reviews, escalations) and expects to read it later.
3. **Your own guardrails block the cleanup.** `AGENTS.md` says "Never modify `misc/` or `demo_seed.sql`," and the README says the seed includes tasks. If you drop the `tasks` table, the seed import fails. Either lift that rule on purpose for this migration and update the seed, or create `demo_seed_lean.sql`. Do this before pointing Gemini or any agent at the removal, or it will refuse the cut or work around it badly. Also, `misc/` may contain things that reference tasks, so check it.
4. **"Reject" probably isn't a state yet.** The README says mentors "approve or request revisions." Your plan has approve, criticize, and reject, so you'd add a `rejected` status (reason required) and decide how a rejected log can be resubmitted.
5. **Notifications will shrink.** Task assignment goes away. Keep join requests, invitations, reviews, and escalations.

**What I'd keep and cut**

|Keep|Cut|
|:--|:--|
|Classrooms, contextual roles, hub|Kanban board, calendar|
|Marketplace and join flow|Contribution pie|
|Weekly logs, attendance, review|Heatmap|
|Escalation flare|Audit log feed|
|Report assistant (fed by weekly logs, not tasks)|Task-assignment notifications|
|Deliverables, attached to a weekly log|`tasks` table, after a quiet period|

**Housekeeping**

- `AGENTS.md` and `gemini.md` are byte-identical. Keep one as the source of truth and have the other point to it, or they will drift.
- After the cut, update the invariants and the README. "Kanban & Milestone Engine," "Dynamic Activity Heatmap," and "14 tables" will all be wrong.
- The README says no SQL in views, and `AGENTS.md` says SQL belongs "in controllers or endpoints," while your study guide says "exclusively in repositories." Pick one rule so NotebookLM and any agent give consistent answers.
- The study guide chapters on task cards and the Add Task trace need new examples. A weekly log submission is the natural replacement for the 8-step trace.

**Order of work**

1. Branch, tag, and back up the DB.
2. Update the `AGENTS.md` protected-paths rule and decide on the seed.
3. Rewire phases (classroom dates), deliverables (to weekly logs), the teacher health dots (from log status), and the report assistant (from logs).
4. Add the `rejected` status and a required comment on non-approve reviews.
5. Delete the UI, then the endpoints, then the dead repository functions.
6. Run for a while with `tasks` untouched, then drop it in a later migration.

If you paste the `phase_repository`, the teacher health query, and the report assistant's data-gathering code, I can say exactly what each one reads from `tasks` and how to repoint it.

That's a much stronger product. "Students log progress weekly, mentor reviews and steers" is easy to explain, and it matches how college projects are graded. Everything you're cutting (board, contribution pie, heatmap, activity log) is monitoring data nobody acts on, while the weekly log is where a mentor actually makes decisions.

**What to check before you cut**

Removing those four features is the easy part. These things probably read from `tasks` or the activity log, so check each one:

- **Generate Report Assistant.** It likely pulls from tasks, the audit log, or both to build the report. If so, it needs a new data source. The natural one is the weekly logs themselves, which is better anyway, since a report built from approved weekly entries is more meaningful than one built from card movements.
- **Deliverables.** Uploads currently hang off task cards. Re-attach them to a weekly log entry or a phase. Check whether `deliverables.task_id` is a foreign key and what happens to existing rows.
- **Teacher dashboard.** The health dots and phase completion bars probably come from task status and due dates. Redefine them from log data, for example: green if this week's log is submitted and approved, yellow if it's pending review or revision was requested, red if a week was missed.
- **Phase chips and milestones.** If phases stay (Synopsis, Literature Survey, and so on), tag each weekly log with a phase instead of tagging tasks.
- **Escalation flare / issues.** It's independent of the board, so it can stay, but confirm it doesn't join against tasks.
- **The student screen.** "My Assigned Tasks" goes away, so decide what members see instead. Probably their team's log history, the mentor's feedback, and their own contribution note per week.

**Design the review workflow properly**

You said approve, criticize, or reject. I'd model it as explicit states, with a comment required for anything other than approve:

- `submitted`
- `approved`
- `revision_requested` (the "criticize" case, with feedback)
- `rejected` (direction not acceptable, with reason)

Treat `revision_requested` as a loop: the team edits and resubmits the same week's log, and the history is kept. Without that, "rejected" becomes a dead end for the student. Store reviewer, timestamp, and comment on every decision, which replaces the audit log for the one thing that matters.

Losing the contribution pie means you lose individual accountability. A cheap replacement is a per-member line in each weekly log ("what I did this week") plus the attendance you already collect. That is more useful to a mentor than a donut chart.

**Safe removal order**

1. Branch, tag, and back up the DB.
2. Grep for `task`, `tasks`, `audit`, `activity`, `heatmap`, `contribution`, and `task_id` across PHP, JS, and SQL. Note every reader outside the board.
3. Rewire the dependents first (report assistant, deliverables, teacher health) onto weekly logs, and test them.
4. Delete the UI, then the endpoints (`add_task.php`, `update_task_status.php`, and whatever feeds the activity log), then the dead repository functions.
5. Stop writing to the old tables and run that way for a while. Drop them in a later migration, after a backup.

Removing the board eliminates the task-status authorization gap I flagged earlier. In its place, scrutinize the weekly log endpoints. Make sure only members of that project can submit, only the assigned mentor (and the coordinator) can review, and that a reviewer can't act on another project's log by changing an ID. Also check file upload handling for type and size limits and storage outside the web root, since those uploads will now be the core feature.

If you paste `leader_dashboard.php`, the weekly log repository, and the queries behind the teacher health dots and the report assistant, I can tell you exactly what depends on `tasks` and sketch the rewiring.

