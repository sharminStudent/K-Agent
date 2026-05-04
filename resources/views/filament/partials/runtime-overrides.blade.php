<style>
    .fi-logo {
        width: auto !important;
        min-width: 0 !important;
        max-width: 9rem !important;
        height: 2.75rem !important;
        object-fit: contain !important;
        object-position: left center !important;
    }

    :root:not(.dark) .fi-sidebar,
    :root:not(.dark) .fi-sidebar-header,
    :root:not(.dark) .fi-topbar,
    :root:not(.dark) .fi-page,
    :root:not(.dark) .fi-section,
    :root:not(.dark) .fi-ta-ctn,
    :root:not(.dark) .fi-dropdown-panel,
    :root:not(.dark) .fi-modal-window,
    :root:not(.dark) .fi-input-wrp,
    :root:not(.dark) .fi-select-input,
    :root:not(.dark) .fi-fo-field-wrp .choices,
    :root:not(.dark) .fi-fo-rich-editor-toolbar,
    :root:not(.dark) .fi-fo-rich-editor-editor,
    :root:not(.dark) .fi-wi-chart .fi-section-content,
    :root:not(.dark) .fi-wi-stats-overview-stat {
        background: #ffffff !important;
        border-color: rgba(211, 3, 61, 0.12) !important;
    }

    :root.dark .fi-body,
    :root.dark .fi-layout,
    :root.dark .fi-main-ctn,
    :root.dark .fi-main,
    :root.dark .fi-page {
        background: #0a0a0a !important;
        color: #f5f5f5 !important;
    }

    .fi-main-ctn,
    .fi-main,
    .fi-page,
    .fi-page-content {
        background: transparent !important;
        box-shadow: none !important;
        --tw-shadow: 0 0 #0000 !important;
        --tw-ring-shadow: 0 0 #0000 !important;
    }

    :root.dark .fi-sidebar,
    :root.dark .fi-sidebar-header,
    :root.dark .fi-topbar,
    :root.dark .fi-page,
    :root.dark .fi-section,
    :root.dark .fi-ta-ctn,
    :root.dark .fi-dropdown-panel,
    :root.dark .fi-modal-window,
    :root.dark .fi-input-wrp,
    :root.dark .fi-select-input,
    :root.dark .fi-fo-field-wrp .choices,
    :root.dark .fi-fo-rich-editor-toolbar,
    :root.dark .fi-fo-rich-editor-editor,
    :root.dark .fi-wi-chart .fi-section-content,
    :root.dark .fi-wi-stats-overview-stat {
        background: #111111 !important;
        border-color: rgba(211, 3, 61, 0.22) !important;
    }

    :root:not(.dark) .fi-sidebar-group-label,
    :root:not(.dark) .fi-page-header-description,
    :root:not(.dark) .fi-ta-header-description,
    :root:not(.dark) .fi-section-header-description,
    :root:not(.dark) .fi-sidebar-item-description,
    :root:not(.dark) .fi-wi-stats-overview-stat-label,
    :root:not(.dark) .fi-wi-stats-overview-stat-description,
    :root:not(.dark) .fi-fo-field-wrp-helper-text {
        color: #111111 !important;
    }

    :root.dark .fi-sidebar-item-label,
    :root.dark .fi-topbar-item-label,
    :root.dark .fi-page-header-heading,
    :root.dark .fi-ta-header-heading,
    :root.dark .fi-wi-stats-overview-stat-value,
    :root.dark .fi-section-header-heading,
    :root.dark .fi-fo-field-wrp-label,
    :root.dark .fi-ta-text,
    :root.dark .fi-sidebar-group-label,
    :root.dark .fi-page-header-description,
    :root.dark .fi-ta-header-description,
    :root.dark .fi-section-header-description,
    :root.dark .fi-sidebar-item-description,
    :root.dark .fi-wi-stats-overview-stat-label,
    :root.dark .fi-wi-stats-overview-stat-description,
    :root.dark .fi-fo-field-wrp-helper-text {
        color: #f5f5f5 !important;
    }

    .fi-simple-layout {
        background:
            linear-gradient(rgba(4, 13, 31, 0.45), rgba(4, 13, 31, 0.68)),
            url('/images/new.jpg') center center / cover no-repeat !important;
    }

    .fi-simple-layout .fi-simple-main-ctn,
    .fi-simple-layout .fi-simple-main,
    .fi-simple-layout .fi-simple-page {
        background: transparent !important;
        box-shadow: none !important;
        --tw-shadow: 0 0 #0000 !important;
        --tw-ring-shadow: 0 0 #0000 !important;
        border: 0 !important;
    }

    .fi-simple-layout .fi-simple-page-content {
        background: rgba(8, 25, 56, 0.94) !important;
        border: 1px solid rgba(148, 163, 184, 0.24) !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 24px 80px rgba(2, 6, 23, 0.45) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        max-width: 26rem !important;
        margin-inline: auto !important;
        padding: 1.75rem !important;
    }

    .fi-simple-layout .fi-simple-page-content .fi-section,
    .fi-simple-layout .fi-simple-page-content .fi-section-content,
    .fi-simple-layout .fi-simple-page-content .fi-fo-component-ctn,
    .fi-simple-layout .fi-simple-page-content .fi-fo-field-wrp,
    .fi-simple-layout .fi-simple-page-content .fi-fo-field-wrp > div {
        background: transparent !important;
    }

    .fi-simple-layout .fi-simple-page-content::before,
    .fi-simple-layout .fi-simple-page-content::after {
        background: transparent !important;
    }

    .fi-simple-layout form {
        max-width: 22rem !important;
        margin-inline: auto !important;
    }

    .fi-simple-layout .fi-simple-header,
    .fi-simple-layout .fi-logo,
    .fi-simple-layout .fi-simple-header-subheading {
        max-width: 22rem !important;
        margin-inline: auto !important;
    }

    .fi-simple-layout .fi-fo-field-label,
    .fi-simple-layout .fi-fo-field-label *,
    .fi-simple-layout .fi-fo-field-label-content,
    .fi-simple-layout .fi-fo-checkbox-list-option-label,
    .fi-simple-layout .fi-fo-checkbox-list-option-label *,
    .fi-simple-layout .fi-fo-radio-option-label,
    .fi-simple-layout .fi-fo-radio-option-label *,
    .fi-simple-layout [data-field-wrapper] label,
    .fi-simple-layout [data-field-wrapper] label *,
    .fi-simple-layout .fi-simple-header,
    .fi-simple-layout .fi-simple-header * {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    .fi-simple-layout .fi-fo-field-label a,
    .fi-simple-layout .fi-fo-field-label a *,
    .fi-simple-layout [data-field-wrapper] a,
    .fi-simple-layout [data-field-wrapper] a * {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    .ka-logout-nav-item .fi-sidebar-item-btn,
    .ka-logout-nav-item .fi-sidebar-item-label,
    .ka-logout-nav-item .fi-sidebar-item-icon {
        color: #dc2626 !important;
        fill: currentColor !important;
        stroke: currentColor !important;
    }

    :root:not(.dark) .ka-analytics-widget .fi-section {
        background:
            radial-gradient(circle at top left, rgba(211, 3, 61, 0.18), transparent 30%),
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 26%),
            linear-gradient(180deg, #fff7fa 0%, #fffdf8 52%, #ffffff 100%) !important;
        border-color: rgba(211, 3, 61, 0.22) !important;
        box-shadow: 0 22px 48px rgba(211, 3, 61, 0.08) !important;
    }

    :root:not(.dark) .ka-analytics-widget .fi-section-content {
        background: transparent !important;
    }

    :root:not(.dark) .ka-analytics-widget .fi-section-header-heading,
    :root:not(.dark) .ka-analytics-widget .fi-section-header-description,
    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-value {
        color: #111827 !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill {
        border-color: rgba(211, 3, 61, 0.1) !important;
        background: rgba(255, 255, 255, 0.78) !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05) !important;
        backdrop-filter: blur(10px) !important;
        -webkit-backdrop-filter: blur(10px) !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-label {
        color: #64748b !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-amber {
        border-color: rgba(245, 158, 11, 0.32) !important;
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.98), rgba(255, 255, 255, 0.9)) !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-amber .ka-analytics-pill-label {
        color: #d97706 !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-teal {
        border-color: rgba(211, 3, 61, 0.18) !important;
        background: linear-gradient(180deg, rgba(255, 241, 242, 0.98), rgba(255, 255, 255, 0.9)) !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-teal .ka-analytics-pill-label {
        color: #d3033d !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-rose {
        border-color: rgba(251, 113, 133, 0.3) !important;
        background: linear-gradient(180deg, rgba(254, 242, 242, 0.98), rgba(255, 255, 255, 0.92)) !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-rose .ka-analytics-pill-label {
        color: #e11d48 !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-pill-slate {
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(255, 255, 255, 0.92)) !important;
    }

    :root:not(.dark) .ka-analytics-widget .ka-analytics-canvas-ctn {
        background:
            radial-gradient(circle at top left, rgba(211, 3, 61, 0.08), transparent 32%),
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.08), transparent 28%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(255, 251, 252, 0.94)) !important;
        border-color: rgba(211, 3, 61, 0.12) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 10px 24px rgba(211, 3, 61, 0.05) !important;
    }

    :root.dark .ka-analytics-widget .fi-section {
        background:
            radial-gradient(circle at top left, rgba(211, 3, 61, 0.34), transparent 28%),
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.28), transparent 24%),
            linear-gradient(135deg, #111827 0%, #020617 52%, #172033 100%) !important;
        border-color: rgba(211, 3, 61, 0.24) !important;
    }

    :root.dark .ka-analytics-widget .fi-section-content {
        background: transparent !important;
    }

    :root.dark .ka-analytics-widget .ka-analytics-pill {
        border-color: rgba(255, 255, 255, 0.08) !important;
        background: rgba(255, 255, 255, 0.05) !important;
        box-shadow: none !important;
    }

    :root.dark .ka-analytics-widget .ka-analytics-canvas-ctn {
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02)),
            rgba(2, 6, 23, 0.32) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06) !important;
    }
</style>

<form method="POST" action="{{ \Filament\Facades\Filament::getLogoutUrl() }}">
    @csrf

    <x-filament::modal
        id="company-logout-confirmation"
        heading="Log out"
        description="Are you sure you want to log out of this control panel?"
        :close-button="true"
        width="sm"
    >
        <x-slot name="footer">
            <div class="fi-modal-footer-actions flex w-full justify-end">
                <x-filament::button
                    type="button"
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'company-logout-confirmation' })"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button type="submit">
                    Logout
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</form>
