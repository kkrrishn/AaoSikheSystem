<?php
http_response_code(500);
$pageTitle = $pageTitle ?? 'Server Error';
$errorMessage = $errorMessage ?? 'An internal server error occurred.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - AaoSikheSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-content {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        .error-icon {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 1.5rem;
        }
        .error-code {
            font-size: 3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-server"></i>
            </div>
            <div class="error-code">500</div>
            <h1 class="error-message"><?= htmlspecialchars($errorMessage) ?></h1>
            <p class="text-muted mb-4">
                We're experiencing some technical difficulties. Our team has been notified 
                and is working to fix the issue. Please try again later.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Go Home
                </a>
                <a href="javascript:location.reload()" class="btn btn-outline-primary">
                    <i class="fas fa-redo me-2"></i>Refresh Page
                </a>
                <a href="/contact" class="btn btn-outline-secondary">
                    <i class="fas fa-envelope me-2"></i>Contact Support
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>