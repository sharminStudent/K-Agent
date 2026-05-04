# Python AI Toolkit

This folder adds a real Python-based AI workflow to `k-agent` without replacing the existing Laravel RAG runtime.

It supports three tasks:

1. Export chat conversations from PostgreSQL into JSONL for supervised fine-tuning.
2. Create an OpenAI fine-tuning job from that JSONL dataset.
3. Chunk and embed text knowledge files in Python for experiments, offline prep, or model-data workflows outside the main PHP ingestion path.

## Setup

Use the same `.env` file as the Laravel app.

```powershell
cd C:\Users\Sharmin\PhpstormProjects\k-agent
python -m venv .venv
.venv\Scripts\Activate.ps1
pip install -r python\requirements.txt
```

## Export Fine-Tuning Data

Exports assistant conversations from `chat_sessions` and `chat_messages` into OpenAI chat fine-tuning JSONL.

```powershell
python python\export_finetune_dataset.py --output storage\app\ai\fine_tune_dataset.jsonl
```

Optional filters:

```powershell
python python\export_finetune_dataset.py --agent-id 1 --limit 200 --output storage\app\ai\agent_1_dataset.jsonl
```

## Create a Fine-Tuning Job

The exported JSONL can be uploaded and used to start a supervised fine-tuning job.

```powershell
python python\create_finetune_job.py ^
  --training-file storage\app\ai\fine_tune_dataset.jsonl ^
  --model gpt-4.1-mini-2025-04-14
```

This prints the uploaded file id and the fine-tuning job id.

## Check Fine-Tuning Status And Events

Use these scripts to inspect the top-level job state and the event log.

```powershell
python python\check_finetune_job.py --job-id ftjob-abc123
python python\check_finetune_events.py --job-id ftjob-abc123 --limit 20
```

## Embed Knowledge in Python

This script chunks a text file, sends those chunks to OpenAI embeddings, and writes the result to JSON.

```powershell
python python\embed_knowledge.py ^
  --input storage\app\knowledge-processed\1\example-text.txt ^
  --output storage\app\ai\example-embeddings.json
```

You can also point it at any `.txt`, `.md`, `.csv`, or already-extracted knowledge text artifact.

## Notes

- The Laravel app still handles live widget chat, guardrails, and RAG retrieval.
- Fine-tuning is best used for response style, structure, and behavior.
- RAG is still the correct mechanism for frequently changing business knowledge uploaded through Filament.
