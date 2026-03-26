<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
</head>

<body class="flex items-center justify-center min-h-screen bg-gray-100">

    <div class="bg-white shadow-lg rounded-lg p-8 text-center max-w-md">
        <h1 class="text-2xl font-bold text-red-600 mb-4">
            Access Denied
        </h1>

        <p class="text-gray-700 mb-6">
            You are not allowed to access this system.
        </p>

        @if (session('error'))
            <p class="text-sm text-red-500 mb-4">
                {{ session('error') }}
            </p>
        @endif

    </div>

</body>

</html>
