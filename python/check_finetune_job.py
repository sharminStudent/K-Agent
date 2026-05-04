from __future__ import annotations

import argparse
import json
import os
import urllib.request
from pathlib import Path


def load_env_file() -> None:
    env_path = Path(__file__).resolve().parents[1] / ".env"
    if not env_path.exists():
        return

    for raw_line in env_path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        key = key.strip()
        value = value.strip().strip('"').strip("'")

        if key and key not in os.environ:
            os.environ[key] = value


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Check an OpenAI fine-tuning job status.")
    parser.add_argument("--job-id", required=True, help="Fine-tuning job id.")
    return parser.parse_args()


def main() -> None:
    load_env_file()
    args = parse_args()

    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY is required.")

    request = urllib.request.Request(
        f"https://api.openai.com/v1/fine_tuning/jobs/{args.job_id}",
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        },
        method="GET",
    )

    with urllib.request.urlopen(request) as response:
        payload = json.loads(response.read().decode("utf-8"))

    print(
        json.dumps(
            {
                "id": payload.get("id"),
                "status": payload.get("status"),
                "model": payload.get("model"),
                "fine_tuned_model": payload.get("fine_tuned_model"),
                "training_file": payload.get("training_file"),
                "trained_tokens": payload.get("trained_tokens"),
                "error": payload.get("error"),
                "finished_at": payload.get("finished_at"),
            }
        )
    )


if __name__ == "__main__":
    main()
