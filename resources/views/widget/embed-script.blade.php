(function () {
    var widgetToken = @json($widgetToken);
    var frameUrl = @json($frameUrl);
    var companyName = @json($companyName);
    var launcherId = 'k-agent-widget-launcher-' + widgetToken;
    var frameId = 'k-agent-widget-frame-' + widgetToken;
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
    frame.style.right = '24px';
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

    function syncState() {
        frame.style.opacity = isOpen ? '1' : '0';
        frame.style.pointerEvents = isOpen ? 'auto' : 'none';
        frame.style.transform = isOpen ? 'translateY(0) scale(1)' : 'translateY(12px) scale(0.98)';
        frame.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        launcher.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        launcher.innerHTML = isOpen ? closeIcon : chatIcon;
    }

    launcher.addEventListener('click', function () {
        isOpen = !isOpen;
        syncState();
    });

    window.addEventListener('message', function (event) {
        if (!event || !event.data || event.data.source !== 'k-agent-widget') {
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

    syncState();
    document.body.appendChild(frame);
    document.body.appendChild(launcher);
})();
