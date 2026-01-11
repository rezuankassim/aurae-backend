<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .success { color: #27ae60; }
        .failed { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h2>Processing Payment...</h2>
        <p>Please do not close this window.</p>
        @if(isset($status))
            <p class="{{ $status === 'success' ? 'success' : 'failed' }}">
                Payment {{ $status === 'success' ? 'Successful' : 'Failed' }}
            </p>
        @endif
    </div>
</body>
</html>
