from fastapi import HTTPException
import json
import re
from pathlib import Path

from app.schemas.sql import GenerateSqlRequest


PROMPT_PATH = Path(__file__).resolve().parents[2] / "prompts" / "sql" / "sql_v1.txt"


def load_prompt_template() -> str:
    if not PROMPT_PATH.exists():
        raise RuntimeError(f"Prompt file not found: {PROMPT_PATH}")
    return PROMPT_PATH.read_text(encoding="utf-8")


def build_sql_prompt(payload: GenerateSqlRequest) -> str:
    tpl = load_prompt_template()
    return tpl.format(
        difficulty=payload.difficulty,
        topic=payload.topic or "",
        extra_context=payload.extra_context or "",
        language=payload.language,
    ).strip()


def extract_json_best_effort(text: str) -> dict:
    if not text:
        raise HTTPException(status_code=422, detail="Empty response from model.")

    s = text.strip()

    m = re.search(r"```(?:json)?\s*(\{.*?\})\s*```", s, flags=re.DOTALL | re.IGNORECASE)
    if m:
        candidate = m.group(1).strip()
        try:
            return json.loads(candidate)
        except json.JSONDecodeError as e:
            raise HTTPException(status_code=422, detail=f"Invalid JSON inside code block: {e}")

    start = s.find("{")
    end = s.rfind("}")
    if start != -1 and end != -1 and end > start:
        candidate = s[start:end + 1].strip()
        try:
            return json.loads(candidate)
        except json.JSONDecodeError as e:
            raise HTTPException(status_code=422, detail=f"Invalid JSON extracted by braces: {e}")

    try:
        return json.loads(s)
    except json.JSONDecodeError as e:
        raise HTTPException(status_code=422, detail=f"Invalid JSON from model: {e}")
