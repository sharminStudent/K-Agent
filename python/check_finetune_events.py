from __future__ import annotations

import argparse
import os
import urllib.parse
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
    parser = argparse.ArgumentParser(description="Check OpenAI fine-tuning job events.")
    parser.add_argument("--job-id", required=True, help="Fine-tuning job id.")
    parser.add_argument(
        "--limit",
        type=int,
        default=20,
        help="Maximum number of events to return.",
    )
    return parser.parse_args()


def main() -> None:
    load_env_file()
    args = parse_args()

    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY is required.")

    query = urllib.parse.urlencode({"limit": args.limit})
    request = urllib.request.Request(
        f"https://api.openai.com/v1/fine_tuning/jobs/{args.job_id}/events?{query}",
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        },
        method="GET",
    )

    with urllib.request.urlopen(request) as response:
        print(response.read().decode("utf-8"))


if __name__ == "__main__":
    main()
