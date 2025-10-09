<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>QR Test</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f5f5f5;
        }

        img {
            border: 4px solid #333;
            border-radius: 8px;
            background: white;
            padding: 10px;
        }
    </style>
</head>

<body>
    <h1>QR Code Preview</h1>
    <p>Data encoded: <strong>{{ $data }}</strong></p>

    <img src="data:image/png;base64,{{ $qr }}" alt="QR Code">

    <p style="margin-top: 20px; color: #555;">If you see a QR above, base64 embedding works fine.</p>
</body>

</html>
