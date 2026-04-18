<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $agent->company_name }} Chat</title>
    @livewireStyles
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: transparent;
        }

        body {
            display: block;
        }

        body > [wire\:id] {
            display: block;
            width: 100%;
            height: 100%;
        }
    </style>
    <script>
        window.kAgentReverb = {
            enabled: @js(filled(config('broadcasting.connections.reverb.key'))),
            key: @js(config('broadcasting.connections.reverb.key')),
            host: @js(config('broadcasting.connections.reverb.options.host') ?: request()->getHost()),
            port: @js((int) (config('broadcasting.connections.reverb.options.port') ?: 8080)),
            forceTLS: @js(config('broadcasting.connections.reverb.options.scheme') === 'https'),
        };
    </script>
    @vite(['resources/js/app.js'])
</head>
<body>
    <livewire:widget.chat-frame
        :agent="$agent"
        :bootstrap-url="$bootstrapUrl"
        :help-url="$helpUrl"
        :help-article-base-url="$helpArticleBaseUrl"
        :light-logo-url="$lightLogoUrl"
        :dark-logo-url="$darkLogoUrl"
    />

    @livewireScripts
</body>
</html>
