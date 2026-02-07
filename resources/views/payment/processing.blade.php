<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment {{ isset($status) && $status === 'success' ? 'Successful' : (isset($status) ? 'Failed' : 'Processing') }}</title>
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
            max-width: 320px;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .success { color: #27ae60; }
        .failed { color: #e74c3c; }
        .message {
            color: #666;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        @if(isset($status) && $status === 'success')
            <div class="icon success">✓</div>
            <h2 class="success">Payment Successful</h2>
            <p class="message">Your payment has been processed successfully.</p>
        @elseif(isset($status) && $status === 'failed')
            <div class="icon failed">✕</div>
            <h2 class="failed">Payment Failed</h2>
            <p class="message">Your payment could not be processed. Please try again.</p>
        @else
            <div class="icon">⏳</div>
            <h2>Processing Payment...</h2>
            <p class="message">Please wait while we process your payment.</p>
        @endif
    </div>
</body>
</html>
