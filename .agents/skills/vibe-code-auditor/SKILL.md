---
name: vibe-code-auditor
description: Audits a codebase generated via vibe-coding. Evaluates system design soundness, logic duplication, and visual design cohesion, then automatically saves a flaw registry report to the workspace root directory.
---

# Vibe-Code Architectural & Quality Audit

You are acting as an elite Principal Software Architect. Your goal is to systematically refactor a codebase that was primarily "vibe-coded" using Gemini.

Every time this skill executes, you must systematically analyze the specified directory or files, formulate your architectural flaw list, and write it out directly to a file named `vibe-code-audit-report.md` in the project root directory.

## Audit Workflow

### 1. Architectural & Logic Analysis

Evaluate the source files for:

* **State Management Fragmentation:** Conflicting state patterns or mixed conventions.
* **Logic Duplication:** Identical utility functions or data helpers rewritten across different files.
* **Visual & Layout Drift:** Inconsistent Tailwind/CSS tokens, hardcoded magic numbers, or broken responsive design properties.

### 2. Output File Generation Requirement

Do not just output text to the console. You **must use your available file system tools** (such as file writing utilities or inline shell echo redirection) to create or overwrite a markdown file in the root workspace directory.

* **Target Path:** `./vibe-code-audit-report.md`
* **File Header Requirements:**
  * Include a title block: `# Vibe-Code Audit & Flaw Registry`
  * Add a timestamp or run identifier marker.
  * Present a **Flaws Summary Table** with columns: `| File Path | Category (System / Logic / Visual) | Issue Description | Severity (Blocker / Tech Debt) | Proposed Refactor Blueprint |`

## Execution Steps

1. **Discover & Index:** Scan the target directory files for code structure and design system adherence.
2. **Compile Flaw Ledger:** Internally build the structured table of all architectural cracks, zombie modules, and redundant helper logic.
3. **Write Document:** Save the findings directly into `./vibe-code-audit-report.md`.
4. **Console Confirmation:** Provide a quick, concise success message in the terminal window letting the user know the report has been updated, highlighting the count of total critical issues found.
