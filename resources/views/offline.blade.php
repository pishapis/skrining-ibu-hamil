<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <style>
        :root {
            --primary-color: #0ea5e9;
            --text-color: #333;
            --background-color: #f0f2f5;
            --card-background-color: #fff;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--background-color);
            color: var(--text-color);
            text-align: center;
        }

        .container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: var(--card-background-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .status-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        h1 {
            font-size: 1.8em;
            font-weight: 600;
            margin: 0 0 10px;
        }

        p {
            font-size: 1em;
            line-height: 1.5;
            margin-bottom: 30px;
            color: #666;
        }

        .retry-button {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-size: 1em;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .retry-button:hover {
            background-color: #0369a1;
            transform: translateY(-2px);
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-15px);
            }

            60% {
                transform: translateY(-7px);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="status-icon">
            &#x1F6B6; </div>
        <h1>Ups, Koneksi Terputus</h1>
        <p>Anda sedang tidak terhubung ke internet. Pastikan perangkat Anda terhubung ke jaringan.</p>
        <button onclick="window.location.reload()" class="retry-button">Coba Lagi</button>
    </div>
</body>

</html>