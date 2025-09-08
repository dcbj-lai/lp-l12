<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
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
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 420px;
            background: var(--container-bg);
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
            text-align: center;
        }

        .logo {
            margin-bottom: 20px;
        }

        .logo img {
            width: 80px;
            height: auto;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 12px;
        }

        p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        a {
            display: inline-block;
            text-decoration: none;
            padding: 10px 20px;
            background: var(--accent-color);
            color: #fff;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
        }

        a:hover {
            background: var(--accent-hover);
        }

        @media (max-width: 480px) {
            .container {
                padding: 16px;
                border-radius: 12px;
            }

            h1 {
                font-size: 24px;
            }

            p {
                font-size: 14px;
            }

            a {
                font-size: 14px;
                padding: 8px 16px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('images/lai-logo.png') }}" alt="LAI Logo">
        </div>

        <h1>Thank You!</h1>
        <p>Your visit has been logged successfully. A member of our team will assist you shortly.</p>

        <a href="{{ route('visitor.start') }}">Back to Start</a>
    </div>
</body>

</html>
