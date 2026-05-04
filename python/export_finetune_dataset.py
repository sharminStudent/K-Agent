from __future__ import annotations

import argparse
import json
import subprocess
from pathlib import Path
from typing import Any


def build_examples(messages: list[dict[str, Any]]) -> list[dict[str, Any]]:
    normalized: list[dict[str, str]] = []

    for message in messages:
        role = "assistant" if message["role"] == "assistant" else "user"
        content = (message["content"] or "").strip()

        if not content:
            continue

        normalized.append({"role": role, "content": content})

    if len(normalized) < 2:
        return []

    if normalized[0]["role"] != "user":
        return []

    assistant_count = sum(1 for message in normalized if message["role"] == "assistant")

    if assistant_count == 0:
        return []

    return normalized


def fetch_chat_data(agent_id: int | None, limit: int | None) -> list[dict[str, Any]]:
    php = r"""
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$agentId = getenv('K_AGENT_EXPORT_AGENT_ID');
$limit = getenv('K_AGENT_EXPORT_LIMIT');
$query = App\Models\ChatSession::query()
    ->whereHas('messages')
    ->with(['messages' => fn ($q) => $q->orderBy('id')])
    ->orderByDesc('id');
if ($agentId !== false && $agentId !== '') {
    $query->where('agent_id', (int) $agentId);
}
if ($limit !== false && $limit !== '') {
    $query->limit((int) $limit);
}
$rows = $query->get()->map(function ($session) {
    return [
        'chat_session_id' => $session->id,
        'agent_id' => $session->agent_id,
        'messages' => $session->messages->map(fn ($message) => [
            'role' => $message->role,
            'content' => $message->content,
        ])->values()->all(),
    ];
})->values()->all();
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    env = dict()
    if agent_id is not None:
        env["K_AGENT_EXPORT_AGENT_ID"] = str(agent_id)
    if limit is not None:
        env["K_AGENT_EXPORT_LIMIT"] = str(limit)

    result = subprocess.run(
        ["php", "-r", php],
        cwd=Path(__file__).resolve().parents[1],
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
        env={**__import__("os").environ, **env},
        check=True,
    )

    return json.loads(result.stdout)


def export_dataset(output_path: Path, agent_id: int | None, limit: int | None) -> dict[str, int]:
    exported = 0
    skipped = 0

    output_path.parent.mkdir(parents=True, exist_ok=True)

    with output_path.open("w", encoding="utf-8") as handle:
        for row in fetch_chat_data(agent_id=agent_id, limit=limit):
            example_messages = build_examples(row["messages"])

            if not example_messages:
                skipped += 1
                continue

            handle.write(json.dumps({"messages": example_messages}, ensure_ascii=True) + "\n")
            exported += 1

    return {"exported": exported, "skipped": skipped}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Export chat history to OpenAI fine-tuning JSONL."
    )
    parser.add_argument("--output", required=True, help="Destination JSONL file path.")
    parser.add_argument("--agent-id", type=int, default=None, help="Restrict export to one agent.")
    parser.add_argument("--limit", type=int, default=None, help="Maximum number of chat sessions.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    summary = export_dataset(Path(args.output), agent_id=args.agent_id, limit=args.limit)
    print(json.dumps(summary))


if __name__ == "__main__":
    main()
