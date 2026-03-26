<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Authorized - Promotion Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a8a; /* Royal Blue */
            --primary-dark: #1e40af;
            --background: #f8fafc;
            --text: #1e293b;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }

        .container {
            max-width: 500px;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .icon {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        p {
            font-size: 1.125rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: #64748b;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .code {
            display: block;
            margin-top: 2rem;
            font-family: monospace;
            font-size: 0.875rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚫</div>
        <h1>Not Authorized</h1>
        <p>You do not have the necessary permissions to access this page. Please contact your administrator if you believe this is an error.</p>
        <a href="/" class="btn">Go back to Home Page</a>
        <span class="code">Error Code: 403 Forbidden</span>
    </div>
</body>
</html>
