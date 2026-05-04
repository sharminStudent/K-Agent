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


def chunk_text(text: str, chunk_size: int = 800, overlap: int = 120) -> list[dict[str, object]]:
    normalized = " ".join(text.split()).strip()

    if not normalized:
        raise RuntimeError("No readable text was found in the input file.")

    chunks: list[dict[str, object]] = []
    offset = 0
    index = 0

    while offset < len(normalized):
        content = normalized[offset : offset + chunk_size]
        chunks.append(
            {
                "index": index,
                "content": content,
                "length": len(content),
            }
        )
        offset += max(1, chunk_size - overlap)
        index += 1

    return chunks


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Chunk a text file and generate OpenAI embeddings in Python."
    )
    parser.add_argument("--input", required=True, help="Path to an extracted text file.")
    parser.add_argument("--output", required=True, help="Destination JSON file path.")
    parser.add_argument(
        "--model",
        default="text-embedding-3-large",
        help="Embedding model name.",
    )
    parser.add_argument("--chunk-size", type=int, default=800)
    parser.add_argument("--overlap", type=int, default=120)
    return parser.parse_args()


def main() -> None:
    load_env_file()
    args = parse_args()
    source = Path(args.input)
    destination = Path(args.output)

    if not source.exists():
        raise FileNotFoundError(f"Input file not found: {source}")

    text = source.read_text(encoding="utf-8")
    chunks = chunk_text(text, chunk_size=args.chunk_size, overlap=args.overlap)

    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY is required.")

    request = urllib.request.Request(
        "https://api.openai.com/v1/embeddings",
        data=json.dumps(
            {
                "model": args.model,
                "input": [chunk["content"] for chunk in chunks],
            }
        ).encode("utf-8"),
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        },
        method="POST",
    )

    with urllib.request.urlopen(request) as response_handle:
        response = json.loads(response_handle.read().decode("utf-8"))

    payload = []

    for chunk, embedding in zip(chunks, response["data"]):
        payload.append(
            {
                "index": chunk["index"],
                "content": chunk["content"],
                "length": chunk["length"],
                "embedding": embedding["embedding"],
            }
        )

    destination.parent.mkdir(parents=True, exist_ok=True)
    destination.write_text(json.dumps(payload, ensure_ascii=True, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "chunks": len(payload),
                "model": args.model,
                "output": str(destination),
            }
        )
    )


if __name__ == "__main__":
    main()
