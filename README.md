# AI Assistant SaaS Platform

This project is a SaaS-style AI assistant platform built on Laravel, Filament, Livewire, Alpine.js, OpenAI, and RAG.

The current repository includes:

- tenant-scoped Filament admin for agents, leads, knowledge, and chat logs
- embeddable widget with live chat, Help articles, archived conversations, and transcript download
- OpenAI-backed chat and embeddings
- RAG over uploaded company knowledge
- Python AI tooling for fine-tuning dataset export, fine-tuning job creation, and embedding workflows

## Stack

- Laravel 13
- PHP 8.4+
- PostgreSQL
- Filament 5
- Livewire 4
- Alpine.js
- Laravel Reverb
- OpenAI Responses API
- OpenAI Embeddings
- Qdrant with local/file fallback
- Python AI utilities

## Local App Run

```powershell
composer install
npm install
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Widget preview example:

```text
http://127.0.0.1:8000/widget/{widget_token}/preview
```

## Current AI Runtime

The live product runtime currently uses RAG, not model training:

1. upload company documents in Filament
2. extract and chunk the text
3. generate embeddings
4. retrieve relevant chunks at question time
5. send retrieved context into the model prompt

This is the right path for frequently changing company knowledge.

## Python AI Toolkit

The project now also includes a real Python layer in [`python/`](python/README.md):

- `export_finetune_dataset.py`
- `create_finetune_job.py`
- `check_finetune_job.py`
- `check_finetune_events.py`
- `embed_knowledge.py`

These scripts can be run directly, or through Artisan wrappers:

```powershell
php artisan ai:export-finetune-dataset
php artisan ai:create-finetune-job
php artisan ai:check-finetune-job ftjob-abc123
php artisan ai:check-finetune-events ftjob-abc123 --limit=20
php artisan ai:embed-knowledge storage\app\some-text-file.txt
```

If Python is not available on the machine, set:

```text
PYTHON_BIN=C:\path\to\python.exe
```

## Fine-Tuning vs RAG

The repo now supports both directions:

- `RAG` for changing business knowledge from Filament uploads
- `Fine-tuning` for tone, format, and assistant behavior using Python-exported JSONL data

RAG remains the primary knowledge mechanism for the widget.

## Railway Continuous Deployment

GitHub CD is configured in `.github/workflows/deploy-railway.yml`.

Deployment runs when the `Tests` workflow succeeds on `main` or `master`, and it can also be run manually from GitHub Actions. The workflow uses the Railway CLI with `railway up --ci`.

Required GitHub Actions secret:

- `RAILWAY_TOKEN`

Optional GitHub Actions secrets:

- `RAILWAY_SERVICE`
- `RAILWAY_ENVIRONMENT`
- `RAILWAY_PROJECT_ID`

Railway deploy behavior is configured in `railway.json`, including the `/up` healthcheck and the pre-deploy commands for migrations and optional seeded demo content.

For production environment values and first-launch recommendations, see [`RAILWAY_DEPLOYMENT.md`](RAILWAY_DEPLOYMENT.md) and start from [`.env.railway.example`](.env.railway.example).

## Notes

- OpenAI chat model should be configured with a valid model id such as `gpt-5.3-chat-latest`
- Widget goodbye intents now return a thank-you message and auto-close without calling OpenAI
- PDF upload is accepted in the UI, but current text extraction support is implemented for TXT, CSV, JSON, and DOCX
- For a simpler first launch on Railway, you can keep `BROADCAST_CONNECTION=null` and `QUEUE_CONNECTION=sync`
- If you want seeded dashboard clients on first deploy, set `SEED_CLIENT_WORKSPACES=true`
