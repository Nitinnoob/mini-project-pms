<?php
session_start();
require 'dbs.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$project_id = $_POST['project_id'] ?? null;
$title = trim($_POST['title'] ?? '');
$assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
$priority = $_POST['priority'] ?? 'normal';

if ($project_id && !empty($title)) {
    $stmt = $pdo->prepare("INSERT INTO tasks (project_id, title, assigned_to, priority, status) VALUES (?, ?, ?, ?, 'todo')");
    $stmt->execute([$project_id, $title, $assigned_to, $priority]);
}

header("Location: dashboard.php");
exit;
