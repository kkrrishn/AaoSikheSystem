<?php
http_response_code(503);
$pageTitle = 'Maintenance Mode';
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
        .maintenance-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-content {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        .maintenance-icon {
            font-size: 4rem;
            color: #74b9ff;
            margin-bottom: 1.5rem;
        }
        .maintenance-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }
        .maintenance-message {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }
        .progress {
            height: 8px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-page">
        <div class="maintenance-content">
            <div class="maintenance-icon">
                <i class="fas fa-tools"></i>
            </div>
            <h1 class="maintenance-title">We'll Be Back Soon!</h1>
            <p class="maintenance-message">
                AaoSikheSystem is currently undergoing scheduled maintenance to improve your experience. 
                We apologize for any inconvenience and appreciate your patience.
            </p>
            
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     style="width: 75%"></div>
            </div>

            <div class="row text-center mb-4">
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <i class="fas fa-rocket fa-2x text-primary mb-2"></i>
                        <h5>Performance</h5>
                        <small class="text-muted">Faster loading times</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                        <h5>Security</h5>
                        <small class="text-muted">Enhanced protection</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h5>Features</h5>
                        <small class="text-muted">New capabilities</small>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-clock me-2"></i>
                <strong>Estimated completion:</strong> 
                <?= date('F j, Y \a\t g:i A', strtotime('+2 hours')) ?>
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="javascript:location.reload()" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i>Check Status
                </a>
                <a href="mailto:support@aaoSikheSystem.com" class="btn btn-outline-primary">
                    <i class="fas fa-envelope me-2"></i>Contact Us
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>