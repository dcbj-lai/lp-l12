<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <style>
        :root {
            --bg-color: #343541;
            --container-bg: #444654;
            --text-color: #d1d5db;
            --accent-color: #1390B4;
            --accent-hover: #1BB8E0;
        }

        @media (prefers-color-scheme: light) {
            :root {
                --bg-color: #f9f9f9;
                --container-bg: #ffffff;
                --text-color: #333;
                --accent-color: #1390B4;
                --accent-hover: #1BB8E0;
            }
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            padding: 20px;
            background: var(--container-bg);
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
            width: 320px;
        }

        h1 {
            font-size: 4em;
            color: var(--accent-color);
            margin-bottom: 0.2em;
        }

        p {
            font-size: 1.2em;
            margin-bottom: 1em;
        }

        a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--accent-hover);
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>500</h1>
        <p>Oops! Something went wrong. <br>We're having trouble at our end. Please refresh the page or try again in a
            few minutes.</p>
        <p><a href="{{ route('dashboard') }}">Back to Dashboard</a></p>
    </div>
</body>

</html>
