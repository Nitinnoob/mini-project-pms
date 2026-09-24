<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management System</title>
    <!-- Include Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* 1. Global Page Background Color */
        body {
            /* A clean, modern plain gradient background */
            background: linear-gradient(135deg, #1e1e2f 0%, #302b63 50%, #24243e 100%);
            height: 100vh;
        }

        /* Loading Screen Styles */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #1e1e2f;
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

        /* 2. Container Position Layout */
        .auth-container {
            max-width: 400px;
            margin-top: 5%;
        }

        /* 3. Custom Login/Register Card Window styling */
        .custom-card {
            background-color: #ffffff; /* pure white window background */
            border-left: 5px solid #6f42c1; /* Stylish purple accent border edge */
            border-radius: 12px;
        }

        /* 4. Custom Submit Button Background Override */
        .btn-custom {
            background-color: #6f42c1; /* Deep primary purple */
            border-color: #6f42c1;
            color: #ffffff;
        }
        .btn-custom:hover {
            background-color: #5a32a3; /* Darker tone on hover effect */
            border-color: #5a32a3;
            color: #ffffff;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

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
