<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'chat_model' => env('OPENAI_CHAT_MODEL'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 30),
    ],

    'qdrant' => [
        'url' => env('QDRANT_URL'),
        'api_key' => env('QDRANT_API_KEY'),
        'collection' => env('QDRANT_COLLECTION', 'agent_knowledge'),
        'timeout' => (int) env('QDRANT_TIMEOUT', 15),
        'distance' => env('QDRANT_DISTANCE', 'Cosine'),
    ],

    'railway' => [
        'api_key' => env('RAILWAY_TOKEN'),
        'project_id' => env('RAILWAY_PROJECT_ID'),
        'environment_id' => env('RAILWAY_ENVIRONMENT'),
        'service_id' => env('RAILWAY_SERVICE'),
    ],

    'rag' => [
        'top_k' => (int) env('RAG_TOP_K', 5),
        'min_keyword_score' => (int) env('RAG_MIN_KEYWORD_SCORE', 1),
        'max_history_messages' => (int) env('RAG_MAX_HISTORY_MESSAGES', 8),
    ],

    'widget' => [
        'chat_idle_timeout_minutes' => (int) env('WIDGET_CHAT_IDLE_TIMEOUT_MINUTES', 30),
    ],

    'assistant' => [
        'instructions_path' => resource_path('prompts/assistant-runtime-instructions.md'),
        'model_routing_enabled' => env('ASSISTANT_MODEL_ROUTING_ENABLED', true),
        'model_guardrails_enabled' => env('ASSISTANT_MODEL_GUARDRAILS_ENABLED', true),
    ],

];
