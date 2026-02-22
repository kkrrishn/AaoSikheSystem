<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to AaoSikheSystem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --accent-color: #06b6d4;
            --text-dark: #1f2937;
            --bg-light: #f9fafb;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: radial-gradient(circle at top right, #e0e7ff, #ffffff);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            text-align: center;
            padding: 2rem;
            max-width: 800px;
            animation: fadeIn 1s ease-in-out;
        }

        h1 {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        p {
            font-size: 1.25rem;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.8rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.39);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            background-color: #4338ca;
        }

        .btn-secondary {
            border: 2px solid #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6;
        }

        .version-tag {
            display: inline-block;
            background: #e0e7ff;
            color: var(--primary-color);
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile Responsive */
        @media (max-width: 600px) {
            h1 { font-size: 2.5rem; }
            .cta-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>

    <div class="container">
        <span class="version-tag">v1.0.0 Beta</span>
        <h1>AaoSikheSystem</h1>
        <p>
            A lightweight, intuitive framework designed to bridge the gap between 
            curiosity and mastery. Build faster, learn deeper, and deploy smarter.
        </p>
        
        <div class="cta-buttons">
            <a href="#docs" class="btn btn-primary">Get Started</a>
            <a href="https://github.com/kkrrishn/AaoSikheSystem" class="btn btn-secondary">View on GitHub</a>
        </div>

        <div style="margin-top: 4rem; font-size: 0.9rem; color: #9ca3af;">
            Press <code style="background: #eee; padding: 2px 5px; border-radius: 4px;">Ctrl + Space</code> to open CLI
        </div>
    </div>

</body>

</html>
