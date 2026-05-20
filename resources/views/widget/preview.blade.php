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
                linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 250, 252, 0.98)),
                #eef2f6;
            color: #142033;
        }

        .page {
            min-height: 100vh;
            padding: 32px 24px 120px;
        }

        .shell {
            max-width: 1180px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 24px;
            border: 1px solid #d8e0e8;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(14px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #17324d, #335f8a);
            color: #f8fbff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .brand-copy strong {
            display: block;
            font-size: 15px;
            font-weight: 700;
        }

        .brand-copy span {
            display: block;
            margin-top: 3px;
            font-size: 13px;
            color: #64748b;
        }

        .nav {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #526275;
        }

        .nav span {
            white-space: nowrap;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(280px, 0.95fr);
            gap: 24px;
            margin-top: 28px;
        }

        .hero,
        .panel,
        .list-card,
        .quote-card {
            border: 1px solid #d8e0e8;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.06);
        }

        .hero {
            padding: 38px;
        }

        .eyebrow {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #7a8ca1;
        }

        h1 {
            max-width: 640px;
            margin: 14px 0 0;
            font-size: clamp(34px, 5vw, 54px);
            line-height: 1.04;
            letter-spacing: -0.04em;
        }

        .hero p {
            max-width: 640px;
            margin: 18px 0 0;
            font-size: 18px;
            line-height: 1.68;
            color: #4b5c6f;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 30px;
        }

        .stat {
            padding: 18px 18px 16px;
            border-radius: 18px;
            background: #f7f9fc;
            border: 1px solid #e2e8f0;
        }

        .stat strong {
            display: block;
            font-size: 24px;
            line-height: 1;
        }

        .stat span {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            color: #64748b;
            line-height: 1.45;
        }

        .column {
            display: grid;
            gap: 24px;
        }

        .panel {
            padding: 24px;
        }

        .panel h2,
        .list-card h2,
        .quote-card h2 {
            margin: 0;
            font-size: 16px;
            letter-spacing: -0.02em;
        }

        .panel p,
        .quote-card p {
            margin: 12px 0 0;
            font-size: 14px;
            line-height: 1.7;
            color: #596b7f;
        }

        .meta-grid {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .meta-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e8edf3;
        }

        .meta-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .meta-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #8b9aae;
        }

        .meta-value {
            max-width: 200px;
            font-size: 14px;
            line-height: 1.55;
            color: #233247;
            text-align: right;
        }

        .list-card {
            padding: 26px;
        }

        .list {
            display: grid;
            gap: 16px;
            margin-top: 18px;
        }

        .list-item {
            padding: 16px 0 0;
            border-top: 1px solid #e8edf3;
        }

        .list-item:first-child {
            padding-top: 0;
            border-top: 0;
        }

        .list-item strong {
            display: block;
            font-size: 15px;
        }

        .list-item span {
            display: block;
            margin-top: 8px;
            font-size: 14px;
            line-height: 1.65;
            color: #5b6c80;
        }

        .quote-card {
            padding: 24px;
            background:
                linear-gradient(180deg, rgba(246, 249, 252, 0.92), rgba(255, 255, 255, 0.96)),
                #ffffff;
        }

        @media (max-width: 980px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .page {
                padding: 18px 16px 120px;
            }

            .topbar,
            .hero,
            .panel,
            .list-card,
            .quote-card {
                border-radius: 18px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav {
                flex-wrap: wrap;
                gap: 12px 16px;
            }

            .hero {
                padding: 28px 22px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="shell">
            <header class="topbar">
                <div class="brand">
                    <div class="brand-mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($agent->company_name, 0, 2)) }}</div>
                    <div class="brand-copy">
                        <strong>{{ $agent->company_name }}</strong>
                        <span>{{ $agent->industry ?: 'Client Services' }}</span>
                    </div>
                </div>

                <div class="nav" aria-hidden="true">
                    <span>Overview</span>
                    <span>Services</span>
                    <span>Case Studies</span>
                    <span>Contact</span>
                </div>
            </header>

            <section class="layout">
                <div class="column">
                    <section class="hero">
                        <p class="eyebrow">Client Site Preview</p>
                        <h1>{{ $agent->company_name }} helps teams make faster, better-informed decisions.</h1>
                        <p>{{ $agent->company_description ?: 'A practical business partner focused on clarity, measurable outcomes, and reliable execution.' }}</p>

                        <div class="stats" aria-hidden="true">
                            <div class="stat">
                                <strong>12+</strong>
                                <span>Active client engagements across product, operations, and support.</span>
                            </div>
                            <div class="stat">
                                <strong>48 hrs</strong>
                                <span>Typical response window for new business and implementation enquiries.</span>
                            </div>
                            <div class="stat">
                                <strong>92%</strong>
                                <span>Of enquiries routed to the right team on the first pass.</span>
                            </div>
                        </div>
                    </section>

                    <section class="list-card">
                        <h2>What clients usually ask first</h2>
                        <div class="list">
                            <div class="list-item">
                                <strong>Scope and fit</strong>
                                <span>Whether the team supports a specific project type, training need, or implementation model.</span>
                            </div>
                            <div class="list-item">
                                <strong>Timing and process</strong>
                                <span>How onboarding works, what the timeline looks like, and what information is needed to get started.</span>
                            </div>
                            <div class="list-item">
                                <strong>Pricing and next steps</strong>
                                <span>What affects cost, who to speak with, and how follow-up is handled after an enquiry.</span>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="column">
                    <section class="panel">
                        <h2>Company Snapshot</h2>
                        <p>Key contact and company details presented in a clean, editorial layout.</p>
                        <div class="meta-grid">
                            <div class="meta-row">
                                <span class="meta-label">Industry</span>
                                <span class="meta-value">{{ $agent->industry ?: 'General Business' }}</span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">Website</span>
                                <span class="meta-value">{{ $agent->website_url ?: 'Not provided' }}</span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">Support</span>
                                <span class="meta-value">{{ $agent->support_email ?: $agent->contact_email ?: 'Contact team' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="quote-card">
                        <h2>About the team</h2>
                        <p>{{ $agent->welcome_message ?: 'The assistant can answer common questions and route more specific requests to the right team.' }}</p>
                    </section>
                </aside>
            </section>
        </div>
    </main>

    <script src="{{ $scriptUrl }}"></script>
</body>
</html>
