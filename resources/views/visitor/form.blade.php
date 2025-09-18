<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Form</title>
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
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 80px;
            height: auto;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            color: #000;
            font-size: 14px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--accent-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 15px;
        }

        button:hover {
            background: var(--accent-hover);
        }

        .error {
            color: #ff4d4d;
            font-size: 0.875em;
            margin-top: 4px;
        }

        @media (max-width: 480px) {
            .container {
                padding: 16px;
                border-radius: 12px;
            }

            button {
                font-size: 14px;
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

        {{-- Visitor Form --}}
        <form method="POST" action="{{ route('visitor.form.submit', $visitor->id) }}">
            @csrf

            <div>
                <label>Full Name</label>
                <input type="text" name="full_name" required value="{{ old('full_name', $visitor->full_name) }}">
                @error('full_name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label>Company</label>
                <input type="text" name="company" required value="{{ old('company', $visitor->company) }}">
                @error('company') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label>Address</label>
                <textarea name="address" required>{{ old('address', $visitor->address) }}</textarea>
                @error('address') <p class="error">{{ $message }}</p> @enderror
            </div>


            <div>
                <label>Mobile Number</label>
                <input type="text" name="mobile" required value="{{ old('mobile', $visitor->mobile) }}">
                @error('mobile') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label>Person to Visit</label>
                <select name="visited_user_id" required>
                    <option value="">-- Select --</option>
                    @foreach($users as $id => $name)
                        <option value="{{ $id }}" {{ old('visited_user_id', $visitor->visited_user_id) == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('visited_user_id') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label>Purpose</label>
                <input type="text" name="purpose" required value="{{ old('purpose', $visitor->purpose) }}">
                @error('purpose') <p class="error">{{ $message }}</p> @enderror
            </div>

            <button type="submit">Submit</button>
        </form>
        <a href="https://laicollege.edu.ph/privacy-policy/" target="_blank" class="privacy-link">
            Privacy Policy
        </a>
    </div>
</body>
<style>
    .privacy-link {
        display: block;
        text-align: center;
        font-size: 0.75rem;
        /* smaller text */
        color: var(--text-color, #666);
        opacity: 0.7;
        text-decoration: none;
        margin-top: 12px;
    }

    .privacy-link:hover {
        opacity: 1;
        text-decoration: underline;
    }
</style>

</html>
