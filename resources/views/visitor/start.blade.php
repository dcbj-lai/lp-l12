<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Check-In</title>
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
            text-align: left;
            padding: 24px;
            background: var(--container-bg);
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 380px;
            box-sizing: border-box;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 80px;
            height: auto;
        }

        h1 {
            font-size: 1.6em;
            color: var(--accent-color);
            margin-bottom: 1.5em;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 0.4em;
            font-size: 0.9em;
            font-weight: bold;
        }

        input {
            display: block;
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 1em;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--accent-color);
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: var(--accent-hover);
        }

        .error {
            color: #f87171;
            font-size: 0.85em;
            margin-top: -0.5em;
            margin-bottom: 1em;
        }

        @media (max-width: 480px) {
            .container {
                padding: 16px;
                border-radius: 12px;
            }

            h1 {
                font-size: 1.4em;
            }

            button {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- Logo --}}
        <div class="logo">
            <img src="{{ asset('images/lai-logo.png') }}" alt="LAI Logo">
        </div>

        <h1>Visitor Check-In</h1>

        {{-- Step 1: Enter email --}}
        @if(!session('step'))
            <form method="POST" action="{{ route('visitor.sendOtp') }}">
                @csrf
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required>
                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror
                <button type="submit">Start</button>
            </form>
        @endif

        {{-- Step 2: Enter OTP --}}
        @if(session('step') === 'verify')
            <form method="POST" action="{{ route('visitor.verifyOtp') }}">
                @csrf
                <input type="hidden" name="email" value="{{ session('email') }}">
                <label for="otp">Enter OTP (sent to {{ session('email') }})</label>
                <input id="otp" type="text" name="otp" maxlength="6" required>
                @error('otp')
                    <p class="error">{{ $message }}</p>
                @enderror
                <button type="submit">Go</button>
            </form>
        @endif
    </div>
</body>

</html>
