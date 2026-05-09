<x-filament-panels::page>
    @php
        $billingAlerts = $this->billingAlerts();
        $accountAlerts = $this->accountAlerts();
        $runtimeAlerts = $this->runtimeAlerts();
    @endphp

    <div class="ka-alert-grid">
        <section class="ka-alert-panel">
            <div class="ka-alert-panel-head">
                <div>
                    <h2>Billing Alerts</h2>
                    <p>Overdue payment records across all client workspaces.</p>
                </div>
                <span class="ka-alert-count">{{ $billingAlerts->count() }}</span>
            </div>

            @if ($billingAlerts->isEmpty())
                <div class="ka-alert-empty">No overdue billing notifications right now.</div>
            @else
                <div class="ka-alert-list">
                    @foreach ($billingAlerts as $alert)
                        <article class="ka-alert-card">
                            <div class="ka-alert-top">
                                <span class="ka-alert-badge ka-alert-badge-{{ $alert['severity'] }}">{{ ucfirst($alert['severity']) }}</span>
                                <a href="{{ $alert['action_url'] }}" class="ka-alert-link">{{ $alert['action_label'] }}</a>
                            </div>
                            <h3>{{ $alert['title'] }}</h3>
                            <p>{{ $alert['body'] }}</p>
                            <dl class="ka-alert-meta">
                                @foreach ($alert['meta'] as $label => $value)
                                    <div>
                                        <dt>{{ $label }}</dt>
                                        <dd>{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="ka-alert-panel">
            <div class="ka-alert-panel-head">
                <div>
                    <h2>Account Status</h2>
                    <p>Clients currently marked as past due, suspended, or canceled.</p>
                </div>
                <span class="ka-alert-count">{{ $accountAlerts->count() }}</span>
            </div>

            @if ($accountAlerts->isEmpty())
                <div class="ka-alert-empty">No client account status issues right now.</div>
            @else
                <div class="ka-alert-list">
                    @foreach ($accountAlerts as $alert)
                        <article class="ka-alert-card">
                            <div class="ka-alert-top">
                                <span class="ka-alert-badge ka-alert-badge-{{ $alert['severity'] }}">{{ ucfirst($alert['severity']) }}</span>
                                <a href="{{ $alert['action_url'] }}" class="ka-alert-link">{{ $alert['action_label'] }}</a>
                            </div>
                            <h3>{{ $alert['title'] }}</h3>
                            <p>{{ $alert['body'] }}</p>
                            <dl class="ka-alert-meta">
                                @foreach ($alert['meta'] as $label => $value)
                                    <div>
                                        <dt>{{ $label }}</dt>
                                        <dd>{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="ka-alert-panel">
            <div class="ka-alert-panel-head">
                <div>
                    <h2>Runtime Tracking</h2>
                    <p>Recent provider and runtime errors recorded for client workspaces.</p>
                </div>
                <span class="ka-alert-count">{{ $runtimeAlerts->count() }}</span>
            </div>

            @if ($runtimeAlerts->isEmpty())
                <div class="ka-alert-empty">No recent runtime alerts right now.</div>
            @else
                <div class="ka-alert-list">
                    @foreach ($runtimeAlerts as $alert)
                        <article class="ka-alert-card">
                            <div class="ka-alert-top">
                                <span class="ka-alert-badge ka-alert-badge-{{ $alert['severity'] }}">{{ ucfirst($alert['severity']) }}</span>
                                <a href="{{ $alert['action_url'] }}" class="ka-alert-link">{{ $alert['action_label'] }}</a>
                            </div>
                            <h3>{{ $alert['title'] }}</h3>
                            <p>{{ $alert['body'] }}</p>
                            <dl class="ka-alert-meta">
                                @foreach ($alert['meta'] as $label => $value)
                                    <div>
                                        <dt>{{ $label }}</dt>
                                        <dd>{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
