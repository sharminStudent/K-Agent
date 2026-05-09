(function () {
    var widgetToken = @json($widgetToken);
    var frameUrl = @json($frameUrl);
    var frameOrigin = new URL(frameUrl, window.location.href).origin;
    var companyName = @json($companyName);
    var launcherId = 'embedded-chat-widget-launcher-' + widgetToken;
    var frameId = 'embedded-chat-widget-frame-' + widgetToken;
    var chatIcon = '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a3 3 0 0 1-3 3H9l-5 3V6a3 3 0 0 1 3-3h11a3 3 0 0 1 3 3Z"></path></svg>';
    var closeIcon = '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M6 6 18 18"></path><path d="M18 6 6 18"></path></svg>';

    if (document.getElementById(launcherId) || document.getElementById(frameId)) {
        return;
    }

    var frame = document.createElement('iframe');
    frame.id = frameId;
    frame.src = frameUrl;
    frame.title = companyName + ' chat widget';
    frame.loading = 'lazy';
    frame.style.position = 'fixed';
    frame.style.top = 'auto';
    frame.style.right = '24px';
    frame.style.left = 'auto';
    frame.style.bottom = '88px';
    frame.style.width = '360px';
    frame.style.maxWidth = 'calc(100vw - 16px)';
    frame.style.height = '500px';
    frame.style.maxHeight = 'calc(100vh - 104px)';
    frame.style.border = '0';
    frame.style.borderRadius = '16px';
    frame.style.boxShadow = '0 20px 52px rgba(0, 0, 0, 0.28)';
    frame.style.zIndex = '2147483646';
    frame.style.overflow = 'hidden';
    frame.style.opacity = '0';
    frame.style.pointerEvents = 'none';
    frame.style.transform = 'translateY(12px) scale(0.98)';
    frame.style.transition = 'opacity 180ms ease, transform 180ms ease';
    frame.setAttribute('aria-hidden', 'true');

    var launcher = document.createElement('button');
    launcher.id = launcherId;
    launcher.type = 'button';
    launcher.setAttribute('aria-expanded', 'false');
    launcher.setAttribute('aria-controls', frameId);
    launcher.setAttribute('aria-label', 'Open chat with ' + companyName);
    launcher.style.position = 'fixed';
    launcher.style.right = '24px';
    launcher.style.bottom = '24px';
    launcher.style.width = '60px';
    launcher.style.height = '60px';
    launcher.style.padding = '0';
    launcher.style.border = '0';
    launcher.style.borderRadius = '999px';
    launcher.style.background = 'linear-gradient(135deg, #d3033d 0%, #8b0f2e 100%)';
    launcher.style.color = '#ffffff';
    launcher.style.fontFamily = 'Segoe UI, sans-serif';
    launcher.style.fontSize = '23px';
    launcher.style.fontWeight = '400';
    launcher.style.cursor = 'pointer';
    launcher.style.boxShadow = '0 18px 45px rgba(211, 3, 61, 0.35)';
    launcher.style.zIndex = '2147483647';
    launcher.style.display = 'grid';
    launcher.style.placeItems = 'center';
    launcher.innerHTML = chatIcon;

    var isOpen = false;

    function applyLayout() {
        var isPhoneScreen = window.matchMedia('(max-width: 640px)').matches;
        var isVeryNarrowScreen = window.matchMedia('(max-width: 420px)').matches;
        var isShortScreen = window.matchMedia('(max-height: 720px)').matches;

        frame.style.top = 'auto';
        frame.style.left = 'auto';

        if (isPhoneScreen) {
            frame.style.right = 'max(12px, env(safe-area-inset-right))';
            frame.style.bottom = 'max(74px, calc(env(safe-area-inset-bottom) + 62px))';
            frame.style.width = isVeryNarrowScreen ? 'calc(100vw - 24px)' : 'min(340px, calc(100vw - 24px))';
            frame.style.maxWidth = 'calc(100vw - 24px)';
            frame.style.height = isVeryNarrowScreen
                ? 'min(420px, calc(100dvh - 98px - env(safe-area-inset-top) - env(safe-area-inset-bottom)))'
                : 'min(460px, calc(100dvh - 98px - env(safe-area-inset-top) - env(safe-area-inset-bottom)))';
            frame.style.maxHeight = 'calc(100dvh - 98px - env(safe-area-inset-top) - env(safe-area-inset-bottom))';
            frame.style.borderRadius = '14px';
        } else {
            frame.style.right = '24px';
            frame.style.bottom = isShortScreen ? '24px' : '88px';
            frame.style.width = '360px';
            frame.style.maxWidth = 'calc(100vw - 16px)';
            frame.style.height = '500px';
            frame.style.maxHeight = 'calc(100vh - 48px)';
            frame.style.borderRadius = '16px';
        }

        launcher.style.right = isPhoneScreen ? 'max(12px, env(safe-area-inset-right))' : '24px';
        launcher.style.bottom = isPhoneScreen ? 'max(12px, env(safe-area-inset-bottom))' : '24px';
        launcher.style.width = isPhoneScreen ? '54px' : '60px';
        launcher.style.height = isPhoneScreen ? '54px' : '60px';
    }

    function syncState() {
        frame.style.opacity = isOpen ? '1' : '0';
        frame.style.pointerEvents = isOpen ? 'auto' : 'none';
        frame.style.transform = isOpen ? 'translateY(0) scale(1)' : 'translateY(12px) scale(0.98)';
        frame.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        launcher.style.opacity = isOpen ? '0' : '1';
        launcher.style.pointerEvents = isOpen ? 'none' : 'auto';
        launcher.style.transform = isOpen ? 'scale(0.92)' : 'scale(1)';
        launcher.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        launcher.innerHTML = isOpen ? closeIcon : chatIcon;
    }

    launcher.addEventListener('click', function () {
        isOpen = !isOpen;
        syncState();
    });

    window.addEventListener('message', function (event) {
        if (event.source !== frame.contentWindow) {
            return;
        }

        if (event.origin !== frameOrigin) {
            return;
        }

        if (!event || !event.data || event.data.source !== 'embedded-chat-widget') {
            return;
        }

        if (event.data.type === 'close') {
            isOpen = false;
            syncState();
        }

        if (event.data.type === 'open') {
            isOpen = true;
            syncState();
        }
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen) {
            isOpen = false;
            syncState();
        }
    });

    window.addEventListener('resize', applyLayout);

    applyLayout();
    syncState();
    document.body.appendChild(frame);
    document.body.appendChild(launcher);
})();
