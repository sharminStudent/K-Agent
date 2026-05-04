<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $companyName }} | Professional Courses</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #132238;
            --muted: #55708f;
            --paper: #f3efe7;
            --panel: rgba(255, 255, 255, 0.8);
            --line: rgba(19, 34, 56, 0.1);
            --brand: #0f766e;
            --brand-deep: #0b4f4a;
            --brand-soft: #ccfbf1;
            --accent: #f59e0b;
            --accent-soft: #fef3c7;
            --shadow: 0 24px 70px rgba(19, 34, 56, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background:
                radial-gradient(circle at 0 0, rgba(20, 184, 166, 0.14), transparent 24%),
                radial-gradient(circle at 100% 0, rgba(245, 158, 11, 0.14), transparent 22%),
                linear-gradient(180deg, #f7f4ee 0%, #efe7dc 100%);
            color: var(--ink);
            font-family: Georgia, "Times New Roman", serif;
        }

        body {
            line-height: 1.5;
        }

        .page {
            width: min(1180px, calc(100% - 28px));
            margin: 0 auto;
            padding: 24px 0 96px;
        }

        .glass,
        .hero,
        .section {
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 22px;
            border-radius: 24px;
        }

        .brand {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .brand-kicker,
        .eyebrow,
        .section-label,
        .mini-label {
            font: 700 12px/1.2 "Trebuchet MS", Arial, sans-serif;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .brand-kicker,
        .eyebrow,
        .section-label {
            color: var(--brand-deep);
        }

        .brand-name {
            font-size: 25px;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font: 600 14px/1.2 "Trebuchet MS", Arial, sans-serif;
            color: var(--muted);
        }

        .hero {
            position: relative;
            overflow: hidden;
            margin-top: 22px;
            padding: 42px;
            border-radius: 36px;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(300px, 0.8fr);
            gap: 26px;
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -60px;
            bottom: -80px;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(15, 118, 110, 0.18), transparent 68%);
            pointer-events: none;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        h1 {
            margin-top: 8px;
            font-size: clamp(40px, 7vw, 74px);
            line-height: 0.95;
            max-width: 10ch;
        }

        .hero-copy {
            margin-top: 18px;
            max-width: 60ch;
            font-size: 18px;
            color: var(--muted);
        }

        .hero-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .badge {
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(11, 79, 74, 0.12);
            background: rgba(255, 255, 255, 0.78);
            color: var(--brand-deep);
            font: 700 13px/1 "Trebuchet MS", Arial, sans-serif;
        }

        .hero-actions {
            margin-top: 24px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .button,
        .ghost-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 999px;
            padding: 14px 18px;
            font: 700 14px/1 "Trebuchet MS", Arial, sans-serif;
        }

        .button {
            background: linear-gradient(135deg, var(--brand), #14b8a6);
            color: #f0fdfa;
            box-shadow: 0 18px 30px rgba(15, 118, 110, 0.22);
        }

        .ghost-button {
            color: var(--brand-deep);
            border: 1px solid rgba(11, 79, 74, 0.16);
            background: rgba(255, 255, 255, 0.52);
        }

        .hero-panel,
        .course-card,
        .metric,
        .testimonial,
        .faq-item,
        .footer-card {
            border-radius: 28px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.84);
        }

        .hero-panel {
            padding: 26px;
            display: grid;
            gap: 16px;
            align-self: start;
        }

        .mini-label {
            color: var(--accent);
        }

        .hero-panel h2 {
            font-size: 26px;
            line-height: 1.1;
        }

        .hero-panel p,
        .course-card p,
        .metric p,
        .testimonial p,
        .faq-item p,
        .footer-card p {
            color: var(--muted);
            font-family: "Trebuchet MS", Arial, sans-serif;
        }

        .panel-list {
            display: grid;
            gap: 10px;
            padding: 0;
            margin: 0;
            list-style: none;
            font: 600 14px/1.4 "Trebuchet MS", Arial, sans-serif;
            color: var(--ink);
        }

        .section {
            margin-top: 22px;
            padding: 28px;
            border-radius: 32px;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 18px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 34px;
            line-height: 1;
        }

        .section-copy {
            max-width: 54ch;
            color: var(--muted);
            font: 500 15px/1.6 "Trebuchet MS", Arial, sans-serif;
        }

        .courses,
        .metrics,
        .testimonials,
        .faq {
            display: grid;
            gap: 18px;
        }

        .courses {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .metrics {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .testimonials,
        .faq {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .course-card,
        .metric,
        .testimonial,
        .faq-item,
        .footer-card {
            padding: 22px;
        }

        .course-meta {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--brand-deep);
            font: 700 12px/1 "Trebuchet MS", Arial, sans-serif;
        }

        .course-card h3,
        .testimonial h3,
        .faq-item h3 {
            margin-top: 14px;
            font-size: 24px;
            line-height: 1.1;
        }

        .course-card p,
        .testimonial p,
        .faq-item p {
            margin-top: 12px;
        }

        .course-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
            font: 700 13px/1.2 "Trebuchet MS", Arial, sans-serif;
            color: var(--brand-deep);
        }

        .metric strong {
            display: block;
            font-size: 40px;
            color: var(--brand);
            line-height: 1;
            margin-bottom: 8px;
        }

        .metric span {
            display: block;
            margin-bottom: 8px;
            font: 700 13px/1.2 "Trebuchet MS", Arial, sans-serif;
            color: var(--brand-deep);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .testimonial-name {
            margin-top: 14px;
            font: 700 14px/1.2 "Trebuchet MS", Arial, sans-serif;
            color: var(--ink);
        }

        .faq-item h3 {
            font-size: 20px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
        }

        code {
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
        }

        .embed-code {
            display: block;
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 18px;
            background: #10273a;
            color: #eefbff;
            overflow: auto;
        }

        @media (max-width: 980px) {
            .hero,
            .footer-grid,
            .courses,
            .metrics,
            .testimonials,
            .faq {
                grid-template-columns: 1fr;
            }

            .hero {
                padding: 28px 22px;
            }

            .topbar,
            .section-head {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="topbar glass">
            <div class="brand">
                <span class="brand-kicker">Learning Hub</span>
                <span class="brand-name">{{ $companyName }}</span>
            </div>

            <nav class="nav-links" aria-label="Primary">
                <span>Courses</span>
                <span>Certificates</span>
                <span>Mentors</span>
                <span>Pricing</span>
                <span>Student Stories</span>
            </nav>
        </section>

        <section class="hero">
            <div>
                <p class="eyebrow">{{ $industry ?: 'Professional Education' }}</p>
                <h1>Launch new skills through guided, career-ready courses.</h1>
                <p class="hero-copy">
                    {{ $description ?: 'Browse practical online programs in data, design, business, and product. Students can ask the site assistant about schedules, pricing, and the right course path directly from the floating widget.' }}
                </p>

                <div class="hero-row">
                    <span class="badge">Live cohorts</span>
                    <span class="badge">Weekend friendly</span>
                    <span class="badge">Certificate included</span>
                </div>

                <div class="hero-actions">
                    <a href="#courses" class="button">Explore Courses</a>
                    @if ($websiteHost)
                        <a href="{{ $agent->website_url }}" class="ghost-button" target="_blank" rel="noreferrer">Visit {{ $websiteHost }}</a>
                    @else
                        <a href="#faq" class="ghost-button">View FAQs</a>
                    @endif
                </div>
            </div>

            <aside class="hero-panel">
                <span class="mini-label">Admissions Support</span>
                <h2>Ask the assistant which course fits your goals.</h2>
                <p>The chat bubble on this page is the real embedded widget for this tenant. A visitor can ask about class timings, beginner-friendly tracks, or enrollment steps.</p>
                <ul class="panel-list">
                    <li>Course recommendations by interest</li>
                    <li>Answers about tuition and schedules</li>
                    <li>Lead capture for admissions follow-up</li>
                </ul>
            </aside>
        </section>

        <section class="section" id="courses">
            <div class="section-head">
                <div>
                    <p class="section-label">Featured Courses</p>
                    <h2 class="section-title">Programs learners actually browse.</h2>
                </div>
                <p class="section-copy">This makes the page feel like a real education website instead of a generic placeholder, while the widget still proves the SaaS product works for another client.</p>
            </div>

            <div class="courses">
                <article class="course-card">
                    <span class="course-meta">12 Weeks</span>
                    <h3>Digital Marketing for Small Business Growth</h3>
                    <p>Learn campaign planning, paid ads, content strategy, and reporting with weekly workshops and real case exercises.</p>
                    <div class="course-footer">
                        <span>Starts May 20</span>
                        <span>$240</span>
                    </div>
                </article>

                <article class="course-card">
                    <span class="course-meta">8 Weeks</span>
                    <h3>Data Analytics Foundations</h3>
                    <p>Build confidence with spreadsheets, dashboards, KPIs, and beginner SQL through structured lessons for working professionals.</p>
                    <div class="course-footer">
                        <span>Starts June 2</span>
                        <span>$310</span>
                    </div>
                </article>

                <article class="course-card">
                    <span class="course-meta">10 Weeks</span>
                    <h3>UX Writing and Product Content</h3>
                    <p>Write clearer interfaces, onboarding flows, and product messages with peer feedback and portfolio-based assignments.</p>
                    <div class="course-footer">
                        <span>Starts June 9</span>
                        <span>$275</span>
                    </div>
                </article>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <div>
                    <p class="section-label">Why Students Join</p>
                    <h2 class="section-title">Built for busy professionals.</h2>
                </div>
            </div>

            <div class="metrics">
                <article class="metric">
                    <strong>4.8</strong>
                    <span>Student Rating</span>
                    <p>Average satisfaction score across recent cohorts and mentorship sessions.</p>
                </article>
                <article class="metric">
                    <strong>1,200+</strong>
                    <span>Learners</span>
                    <p>Professionals and graduates have completed at least one certificate program.</p>
                </article>
                <article class="metric">
                    <strong>82%</strong>
                    <span>Completion Rate</span>
                    <p>High completion driven by live support, reminders, and structured milestones.</p>
                </article>
                <article class="metric">
                    <strong>3</strong>
                    <span>Tracks</span>
                    <p>Marketing, analytics, and product communication paths for different goals.</p>
                </article>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <div>
                    <p class="section-label">Student Stories</p>
                    <h2 class="section-title">Testimonials that make the page believable.</h2>
                </div>
            </div>

            <div class="testimonials">
                <article class="testimonial">
                    <h3>"I used the assistant to compare courses before enrolling."</h3>
                    <p>The widget answered basic questions immediately, then the admissions team followed up with the right cohort recommendation for my schedule.</p>
                    <div class="testimonial-name">Mariam A. | Marketing Coordinator</div>
                </article>

                <article class="testimonial">
                    <h3>"It felt like a real training website, not a demo."</h3>
                    <p>I could see pricing, course duration, and certificate info on the site, then open the chat for extra details without leaving the page.</p>
                    <div class="testimonial-name">Yousef H. | Operations Analyst</div>
                </article>
            </div>
        </section>

        <section class="section" id="faq">
            <div class="section-head">
                <div>
                    <p class="section-label">Common Questions</p>
                    <h2 class="section-title">What the widget can help answer.</h2>
                </div>
            </div>

            <div class="faq">
                <article class="faq-item">
                    <h3>Do I need prior experience?</h3>
                    <p>Some tracks are beginner-friendly and some are intermediate. The assistant can guide a visitor to the most suitable starting point.</p>
                </article>
                <article class="faq-item">
                    <h3>Are classes live or self-paced?</h3>
                    <p>This sample site presents a mix of live cohort sessions and structured self-study material with deadlines and mentor check-ins.</p>
                </article>
                <article class="faq-item">
                    <h3>Can visitors ask about pricing in chat?</h3>
                    <p>Yes. The embedded widget can answer program questions and capture leads for follow-up, which makes the demo meaningful.</p>
                </article>
                <article class="faq-item">
                    <h3>Is this still tied to the tenant?</h3>
                    <p>Yes. The page content is presentation only, but the assistant at the bottom-right is the real widget for {{ $companyName }}.</p>
                </article>
            </div>
        </section>

        <section class="section">
            <div class="footer-grid">
                <article class="footer-card">
                    <p class="section-label">Embed Source</p>
                    <h2 class="section-title">Real widget script</h2>
                    <p>This page still loads the actual tenant widget script. That keeps the demo realistic while preserving the proof that the assistant is not hardcoded for one business.</p>
                    <code class="embed-code">&lt;script src="{{ $scriptUrl }}"&gt;&lt;/script&gt;</code>
                </article>

                <article class="footer-card">
                    <p class="section-label">Tenant Details</p>
                    <h2 class="section-title">{{ $companyName }}</h2>
                    <p>Agent: {{ $agent->name }}</p>
                    <p>Slug: {{ $agent->slug }}</p>
                    <p>Widget token: {{ $agent->widget_token }}</p>
                </article>
            </div>
        </section>
    </main>

    <script src="{{ $scriptUrl }}"></script>
</body>
</html>
