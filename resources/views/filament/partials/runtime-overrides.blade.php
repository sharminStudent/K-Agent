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

    :root:not(.dark) .fi-btn.fi-btn-color-primary,
    :root:not(.dark) .fi-btn-color-primary,
    :root:not(.dark) button.fi-btn-color-primary,
    :root:not(.dark) a.fi-btn-color-primary,
    :root:not(.dark) .fi-btn[type='submit'] {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    :root:not(.dark) .fi-btn.fi-btn-color-primary *,
    :root:not(.dark) .fi-btn-color-primary *,
    :root:not(.dark) button.fi-btn-color-primary *,
    :root:not(.dark) a.fi-btn-color-primary *,
    :root:not(.dark) .fi-btn[type='submit'] *,
    :root:not(.dark) .fi-btn[type='submit'] span {
        color: #ffffff !important;
        fill: #ffffff !important;
        stroke: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        opacity: 1 !important;
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
</style>
