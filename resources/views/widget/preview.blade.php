<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $agent->company_name }} Widget Preview</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(211, 3, 61, 0.12), transparent 28%),
                linear-gradient(180deg, #f6f7fb 0%, #eef1f7 100%);
            color: #11213d;
        }

        .page {
            min-height: 100vh;
            padding: 56px 24px 120px;
        }

        .hero {
            max-width: 760px;
        }

        .eyebrow {
            margin: 0 0 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #8b0f2e;
        }

        h1 {
            margin: 0;
            font-size: clamp(34px, 5vw, 56px);
            line-height: 1.02;
        }

        p {
            max-width: 560px;
            margin: 18px 0 0;
            font-size: 18px;
            line-height: 1.6;
            color: #55627d;
        }

        .card {
            margin-top: 34px;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(17, 33, 61, 0.08);
            box-shadow: 0 24px 60px rgba(17, 33, 61, 0.08);
        }

        .dot {
            width: 11px;
            height: 11px;
            border-radius: 999px;
            background: #2bc36b;
            box-shadow: 0 0 0 8px rgba(43, 195, 107, 0.14);
        }

        .caption {
            font-size: 14px;
            color: #34425c;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <p class="eyebrow">Widget Preview</p>
            <h1>{{ $agent->company_name }} Chat Bubble</h1>
            <p>This page exists only for local testing. Use the floating bubble in the bottom-right corner to test the real open and close behavior at the correct widget size.</p>

            <div class="card">
                <span class="dot" aria-hidden="true"></span>
                <span class="caption">The launcher should open a compact chat window instead of taking over the full screen.</span>
            </div>
        </section>
    </main>

    <script src="{{ $scriptUrl }}"></script>
</body>
</html>
