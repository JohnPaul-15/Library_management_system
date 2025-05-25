<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-background {
            background-color: #f8f9fa;
        }
        .bg-background-lighter {
            background-color: #e9ecef;
        }
        .text-accent-orange {
            color: #fd7e14;
        }
        .text-accent-yellow {
            color: #ffc107;
        }
        .btn-primary {
            background-color: #fd7e14;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: #e96b0a;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center bg-background">
        <div class="w-full max-w-md p-8 text-center">
            <div class="mb-8">
                <div class="w-24 h-24 mx-auto mb-6 bg-background-lighter rounded-full flex items-center justify-center">
                    <i class="fas fa-book-open text-5xl text-accent-orange opacity-20"></i>
                </div>
                <h1 class="text-6xl font-bold text-accent-orange mb-4">404</h1>
                <h2 class="text-2xl font-semibold mb-4">Page Not Found</h2>
                <p class="text-gray-400 mb-8">
                    The page you're looking for doesn't exist or has been moved.
                </p>
            </div>

            <div class="space-y-4">
                <a href="{{ route('dashboard') }}" class="btn-primary w-full flex items-center justify-center gap-2">
                    <i class="fas fa-book-open w-5 h-5"></i>
                    Go to Dashboard
                </a>
                <a href="{{ route('home') }}" class="text-accent-orange hover:text-accent-yellow transition-colors">
                    Return to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html> 