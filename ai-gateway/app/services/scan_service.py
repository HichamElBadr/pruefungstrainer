from fastapi import HTTPException
from pathlib import Path

from app.clients.ollama_client import OllamaClient
from app.schemas.scan import GenerateScanRequest
from app.services.json_extractor import extract_json_best_effort


PROMPT_PATH = Path(__file__).resolve().parents[2] / "prompts" / "scan" / "scan_v1.txt"


def load_prompt_template() -> str:
    if not PROMPT_PATH.exists():
        raise RuntimeError(f"Prompt file not found: {PROMPT_PATH}")
    return PROMPT_PATH.read_text(encoding="utf-8")


def build_scan_prompt(payload: GenerateScanRequest) -> str:
    tpl = load_prompt_template()
    return tpl.format(
        difficulty=payload.difficulty,
        extra_context=payload.extra_context or "",
        language=payload.language,
    ).strip()


async def generate_scan_exercise(payload: GenerateScanRequest, client: OllamaClient) -> dict:
    prompt = build_scan_prompt(payload)
    raw = await client.generate(prompt)
    obj = extract_json_best_effort(raw)

    for field in ("task", "solution"):
        if field not in obj or not isinstance(obj[field], str) or not obj[field].strip():
            raise HTTPException(
                status_code=422,
                detail=f"Missing/invalid field '{field}' in model JSON.",
            )

    return {
        "task": obj["task"].strip(),
        "solution": obj["solution"].strip(),
        "meta": {
            "provider": "ollama",
            "model": client.model,
            "timeout_sec": client.timeout_sec,
            "prompt_version": "scan_v1",
        },
    }
