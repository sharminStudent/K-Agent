from __future__ import annotations

import argparse
import json
import mimetypes
import os
import uuid
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
    parser = argparse.ArgumentParser(
        description="Upload a JSONL dataset and create an OpenAI fine-tuning job."
    )
    parser.add_argument("--training-file", required=True, help="Path to a JSONL training file.")
    parser.add_argument(
        "--model",
        default="gpt-4.1-mini-2025-04-14",
        help="Base model to fine-tune.",
    )
    parser.add_argument(
        "--suffix",
        default=None,
        help="Optional suffix for the resulting fine-tuned model.",
    )
    return parser.parse_args()


def main() -> None:
    load_env_file()
    args = parse_args()
    training_file = Path(args.training_file)

    if not training_file.exists():
        raise FileNotFoundError(f"Training file not found: {training_file}")

    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY is required.")

    boundary = f"----KAgentBoundary{uuid.uuid4().hex}"
    content_type = mimetypes.guess_type(training_file.name)[0] or "application/octet-stream"
    file_bytes = training_file.read_bytes()
    body = (
        f"--{boundary}\r\n"
        f'Content-Disposition: form-data; name="purpose"\r\n\r\n'
        f"fine-tune\r\n"
        f"--{boundary}\r\n"
        f'Content-Disposition: form-data; name="file"; filename="{training_file.name}"\r\n'
        f"Content-Type: {content_type}\r\n\r\n"
    ).encode("utf-8") + file_bytes + f"\r\n--{boundary}--\r\n".encode("utf-8")

    upload_request = urllib.request.Request(
        "https://api.openai.com/v1/files",
        data=body,
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": f"multipart/form-data; boundary={boundary}",
        },
        method="POST",
    )

    with urllib.request.urlopen(upload_request) as response:
        uploaded = json.loads(response.read().decode("utf-8"))

    payload = {
        "training_file": uploaded["id"],
        "model": args.model,
    }
    if args.suffix:
        payload["suffix"] = args.suffix

    job_request = urllib.request.Request(
        "https://api.openai.com/v1/fine_tuning/jobs",
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        },
        method="POST",
    )

    with urllib.request.urlopen(job_request) as response:
        job = json.loads(response.read().decode("utf-8"))

    print(
        json.dumps(
            {
                "uploaded_file_id": uploaded["id"],
                "fine_tuning_job_id": job["id"],
                "model": args.model,
                "status": job["status"],
            }
        )
    )


if __name__ == "__main__":
    main()
