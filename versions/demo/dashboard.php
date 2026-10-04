<?php
session_start();
// Prevent caching so the back button doesn't work after logout
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? 'Developer'; // Default to Developer if role is missing
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Project Management</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Loading Screen Styles */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #f4f6f9;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(111, 66, 193, 0.3);
            border-top-color: #6f42c1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 
            to { transform: rotate(360deg); } 
        }
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background-color: #1e1e2f;
        }
        .card-custom {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .card-custom:hover {
            transform: translateY(-2px);
        }
        .progress-slim {
            height: 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<!-- Loading Animation -->
<div id="page-loader">
    <div class="spinner"></div>
</div>

<script>
    window.addEventListener('load', function() {
        const loader = document.getElementById('page-loader');
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.visibility = 'hidden';
        }, 500);
    });
</script>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#"><i class="fas fa-layer-group me-2"></i>PMS Workspace</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3"><i class="fas fa-user-circle me-1"></i> <?php echo $username; ?> <span class="badge bg-primary ms-1"><?php echo $role; ?></span></span>
            <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark">Welcome back, <?php echo $username; ?>!</h2>
            <p class="text-muted">Here is what's happening with your projects today.</p>
        </div>
    </div>

    <!-- ROLE: TEACHER / ADMIN -->
    <?php if ($role === 'Teacher' || $role === 'Admin'): ?>
    <div class="row">
        <h4 class="mb-3 text-secondary border-bottom pb-2"><i class="fas fa-chalkboard-teacher me-2"></i>Classroom Projects Overview</h4>
        
        <!-- Summary Cards -->
        <div class="col-md-3">
            <div class="card card-custom bg-white p-3 border-start border-primary border-4">
                <h6 class="text-muted mb-1">Active Student Groups</h6>
                <h3 class="fw-bold mb-0">12 Groups</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom bg-white p-3 border-start border-success border-4">
                <h6 class="text-muted mb-1">Total Students Enrolled</h6>
                <h3 class="fw-bold mb-0">48 Students</h3>
            </div>
        </div>
        
        <!-- Projects Portfolio List -->
        <div class="col-md-12 mt-3">
            <div class="card card-custom">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-graduation-cap text-primary me-2"></i>Student Project Submissions</span>
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i> Export Grades</button>
                </div>
                <div class="card-body">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Project Topic</th>
                                <th>Team Lead</th>
                                <th>Group Size</th>
                                <th>Completion Status</th>
                                <th>Grading Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-robot text-primary me-2"></i>AI Chatbot Assignment</td>
                                <td>Sarah Jenkins</td>
                                <td>4 Students</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress progress-slim flex-grow-1 me-2">
                                            <div class="progress-bar bg-success" style="width: 90%"></div>
                                        </div>
                                        <span class="small text-muted">90%</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-success">Ready for Review</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-leaf text-info me-2"></i>Smart Greenhouse IoT</td>
                                <td>Mike Ross</td>
                                <td>3 Students</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress progress-slim flex-grow-1 me-2">
                                            <div class="progress-bar bg-warning" style="width: 40%"></div>
                                        </div>
                                        <span class="small text-muted">40%</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-warning text-dark">In Progress</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ROLE: TEAM LEAD / PROJECT MANAGER -->
    <?php if ($role === 'Team Lead' || $role === 'Project Manager'): ?>
    <div class="row">
        <h4 class="mb-3 text-secondary border-bottom pb-2"><i class="fas fa-users-cog me-2"></i>Group Leadership Console</h4>
        
        <!-- Actions -->
        <div class="col-md-12 mb-4">
            <button class="btn btn-primary shadow-sm me-2"><i class="fas fa-user-plus me-2"></i>Add Group Member</button>
            <button class="btn btn-success shadow-sm"><i class="fas fa-calendar-check me-2"></i>Schedule Meeting</button>
        </div>

        <!-- Charts & Analytics -->
        <div class="col-md-6">
            <div class="card card-custom h-100">
                <div class="card-header bg-white fw-bold py-3">Group Task Progress</div>
                <div class="card-body p-4">
                    <canvas id="tasksChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Milestones -->
        <div class="col-md-6">
            <div class="card card-custom h-100">
                <div class="card-header bg-white fw-bold py-3">Assignment Deadlines</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Submit Phase 1 Report</div>
                                <span class="small text-muted">Assigned to: Entire Group</span>
                            </div>
                            <span class="badge bg-danger rounded-pill">Due Tomorrow!</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold text-dark">Finish Presentation Slides</div>
                                <span class="small text-muted">Assigned to: Jessica</span>
                            </div>
                            <span class="badge bg-primary rounded-pill">Due in 4 Days</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Chart.js implementation for PM view
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById('tasksChart');
            if(ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Finished Tasks', 'Working On It', 'Behind Schedule'],
                        datasets: [{
                            data: [50, 30, 20],
                            backgroundColor: ['#198754', '#0d6efd', '#dc3545'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '75%',
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        });
    </script>
    <?php endif; ?>

    <!-- ROLE: STUDENT / DEVELOPER (Last Level) -->
    <?php if ($role === 'Student' || $role === 'Developer'): ?>
    <div class="row">
        <h4 class="mb-3 text-secondary border-bottom pb-2"><i class="fas fa-book-reader me-2"></i>My Assignments</h4>
        
        <div class="col-md-8 mx-auto">
            <div class="card card-custom border-top border-primary border-4 shadow">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-clipboard-list me-2"></i>My Tasks for the Project</span>
                    <span class="badge bg-primary text-white">3 Tasks Pending</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <label class="list-group-item p-3 d-flex gap-3 align-items-center">
                            <input class="form-check-input flex-shrink-0 fs-5" type="checkbox" value="">
                            <span class="pt-1 form-checked-content">
                                <strong>Research competitor algorithms</strong>
                                <small class="d-block text-muted mt-1"><i class="fas fa-book me-1"></i> Part of: Phase 1 Report</small>
                            </span>
                        </label>
                        <label class="list-group-item p-3 d-flex gap-3 align-items-center">
                            <input class="form-check-input flex-shrink-0 fs-5" type="checkbox" value="">
                            <span class="pt-1 form-checked-content">
                                <strong>Write Python script for data processing</strong>
                                <small class="d-block text-muted mt-1"><i class="fas fa-code me-1"></i> Needs to run without errors before Friday</small>
                            </span>
                        </label>
                        <label class="list-group-item p-3 d-flex gap-3 align-items-center">
                            <input class="form-check-input flex-shrink-0 fs-5" type="checkbox" value="">
                            <span class="pt-1 form-checked-content">
                                <strong>Format bibliography</strong>
                                <small class="d-block text-muted mt-1"><i class="fas fa-file-alt me-1"></i> APA format required</small>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <button class="btn btn-sm btn-outline-success"><i class="fas fa-check-double me-2"></i>Turn in completed tasks</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
