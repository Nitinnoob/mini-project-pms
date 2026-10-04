# PMS Context & Guardrails

## 🚀 Environment & Commands
- **Testing**: Manual copy to XAMPP (`C:\xampp\htdocs\pjmgmt`). Dev workspace is for versioning only.
- **Database**: Import `schema.sql` via phpMyAdmin (single source of truth).
- **Build**: `npm run build:css` compiles Tailwind utilities into `assets/css/tailwind.min.css`. Run after adding new utility classes to PHP views. `npm run watch:css` for live recompilation during development.

## 🛠️ Stack & Conventions
- **Core**: PHP 8.x (Procedural + PDO), MySQL/MariaDB, Tailwind CSS (compiled via CLI), Vanilla JS, SortableJS, Chart.js.
- **Pattern**: Master Controller (`dashboard.php`) fetches DB -> populates `$viewData` array -> includes `views/*.php`.
- **Roles**: Contextual per classroom (`classroom_members.role`: 'Admin' or 'Team Member'). `users.role` is legacy/NULL. Mentors are Admins.
- **Styling**: `assets/css/tailwind.min.css` (compiled utilities) + `pms.css` (tokens, components, themes). Themes switch via `data-mode` (student/leader/teacher).
- **DB**: Use `RESTRICT` or `SET NULL` for foreign keys (avoid `CASCADE`). Always use PDO prepared statements.

## 🛑 Guardrails & Anti-Patterns (NEVER DO)
- **NO** database queries inside view files (`views/*.php`). Query in controllers only.
- **NO** coding before planning. Always wait for explicit command to execute.
- **NO** global role checks. Roles are strictly classroom-scoped.
- **NO** modifying `demo/` or `v 1.0.0/` folders (archived snapshots).
- **NO** `htmlspecialchars(null)` in PHP 8+. Use null coalescing `$val ?? ''`.

## 🧠 Behavioral Instructions
1. **Plan First**: Present a 1-2 sentence impact analysis before proposing code changes.
2. **Cross-Validation**: If user shares feedback from another AI, logically verify it before blindly applying.
3. **Security**: Prevent DOM-XSS using `escapeHTML()` in JS.
4. **No Placeholders**: Provide complete, copy-pasteable files. Do not use `// TODO: implement rest`.

## 📝 Common Patterns
### 1. View Data Pattern (PHP)
```php
// dashboard.php (Controller)
$stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
$stmt->execute([$reqId]);
$viewData['project'] = $stmt->fetch(PDO::FETCH_ASSOC);
require 'views/leader.php';

// views/leader.php (View)
$pName = htmlspecialchars($viewData['project']['name'] ?? 'Untitled');
echo "<h1>{$pName}</h1>";
```
### 2. AJAX Fetch Pattern (JS)
```javascript
fetch('endpoint.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${id}&val=${val}`
}).then(res => res.json()).then(data => {
    if (!data.success) alert(escapeHTML(data.error));
});
```
