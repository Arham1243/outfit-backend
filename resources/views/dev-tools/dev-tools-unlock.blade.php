<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev tools</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <style>
        :root {
            --bg: #0b0f14;
            --panel: #10151d;
            --muted: #9aa4b2;
            --border: #283546;
            --accent: #3b82f6;
            --accent-strong: #2563eb;
            --text: #e6edf3;
            --danger: #f87171;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 400px;
            background: var(--panel);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 28px 24px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.35);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        p {
            margin: 0 0 20px;
            font-size: 14px;
            color: var(--muted);
        }

        label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1.25px solid var(--border);
            background: #0e141b;
            color: var(--text);
            font-family: ui-monospace, monospace;
            font-size: 16px;
            letter-spacing: 0.08em;
            outline: none;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .error {
            margin: 10px 0 0;
            font-size: 13px;
            color: var(--danger);
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px 16px;
            border: 0;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(180deg, var(--accent), var(--accent-strong));
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.45);
        }

        button:hover {
            filter: brightness(1.05);
        }
    </style>
</head>

<body>
    <div class="card">
        <form method="POST" action="{{ route('dev-tools.unlock') }}">
            @csrf
            <input type="hidden" name="intended" value="{{ $intended }}">
            <input id="pin" name="pin" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                pattern="\d{6}" title="Enter exactly 6 digits" required autofocus>

            @error('pin')
                <p class="error">{{ $message }}</p>
            @enderror

            <button type="submit">Continue</button>
        </form>
    </div>

    <script>
        (function() {
            const form = document.querySelector('.card form');
            const pin = document.getElementById('pin');
            if (!form || !pin) return;

            pin.addEventListener('input', function() {
                const v = pin.value.replace(/\D/g, '').slice(0, 6);
                pin.value = v;
                if (v.length === 6) {
                    form.requestSubmit();
                }
            });

            pin.addEventListener('paste', function(e) {
                e.preventDefault();
                const t = (e.clipboardData || window.clipboardData).getData('text') || '';
                const v = t.replace(/\D/g, '').slice(0, 6);
                pin.value = v;
                if (v.length === 6) {
                    form.requestSubmit();
                }
            });
        })();
    </script>
</body>

</html>
