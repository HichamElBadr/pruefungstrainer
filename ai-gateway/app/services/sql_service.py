from fastapi import HTTPException
from pathlib import Path

from app.schemas.sql import GenerateSqlRequest
from app.clients.ollama_client import OllamaClient
from app.services.json_extractor import extract_json_best_effort



PROMPT_PATH = Path(__file__).resolve().parents[2] / "prompts" / "sql" / "sql_v1.txt"
DIFFICULTY_RULES = {
    "easy": "simple SELECT queries, WHERE, ORDER BY, and basic filtering. Avoid JOIN, GROUP BY, HAVING, and subqueries.",
    "medium": "JOINs, GROUP BY, and aggregate functions. Use two or three related tables.",
    "hard": "multiple JOINs, HAVING, subqueries, and more complex conditions. Use at least three related tables.",
}


def load_prompt_template() -> str:
    if not PROMPT_PATH.exists():
        raise RuntimeError(f"Prompt file not found: {PROMPT_PATH}")
    return PROMPT_PATH.read_text(encoding="utf-8")


def build_sql_prompt(payload: GenerateSqlRequest) -> str:
    tpl = load_prompt_template()
    return tpl.format(
        difficulty=payload.difficulty,
        difficulty_rules=DIFFICULTY_RULES[payload.difficulty],
        topic=payload.topic or "",
        extra_context=payload.extra_context or "",
        language=payload.language,
    ).strip()

async def generate_sql_exercise(
    payload: GenerateSqlRequest,
    client: OllamaClient,
) -> dict:
    """
    Orchestriert die komplette SQL-Generierung:
    1) Prompt bauen
    2) Ollama aufrufen
    3) JSON extrahieren
    4) Validieren
    """

    prompt = build_sql_prompt(payload)
    raw = await client.generate(prompt, response_format="json")

    obj = extract_json_best_effort(raw)

    for k in ("task", "mysqlstatement", "solution"):
        if k not in obj or not isinstance(obj[k], str) or not obj[k].strip():
            raise HTTPException(
                status_code=422,
                detail=f"Missing/invalid field '{k}' in model JSON.",
            )

    return {
        "task": obj["task"].strip(),
        "mysqlstatement": obj["mysqlstatement"].strip(),
        "solution": obj["solution"].strip(),
        "meta": {
            "provider": "ollama",
            "model": client.model,
            "timeout_sec": client.timeout_sec,
            "prompt_version": "sql_v1",
        },
    }
