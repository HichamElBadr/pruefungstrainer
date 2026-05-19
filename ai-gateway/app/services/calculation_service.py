import re
from pathlib import Path

from fastapi import HTTPException

from app.clients.ollama_client import OllamaClient
from app.schemas.calculation import GenerateCalculationRequest
from app.services.json_extractor import extract_json_best_effort


PROMPT_PATH = Path(__file__).resolve().parents[2] / "prompts" / "calculation" / "calculation_v1.txt"
MAX_GENERATION_ATTEMPTS = 3
REQUIRED_FIELDS = ("title", "task", "expected_result", "expected_unit", "sample_solution")
NUMBER_PATTERN = re.compile(r"(?<![A-Za-zÄÖÜäöüß])\d+(?:[.,]\d+)*(?![A-Za-zÄÖÜäöüß])")
EXPECTED_RESULT_PATTERN = re.compile(r"^[+-]?\d+(?:[.,]\d+)?$")
VAGUE_TASK_PHRASES = (
    "berechnen sie die preise",
    "berechnen sie den wert",
    "lösen sie die aufgabe",
)


def load_prompt_template() -> str:
    if not PROMPT_PATH.exists():
        raise RuntimeError(f"Prompt file not found: {PROMPT_PATH}")
    return PROMPT_PATH.read_text(encoding="utf-8")


def build_calculation_prompt(payload: GenerateCalculationRequest) -> str:
    tpl = load_prompt_template()
    return tpl.format(
        difficulty=payload.difficulty,
        topic=payload.topic,
        extra_context=payload.extra_context or "",
        language=payload.language,
    ).strip()


async def generate_calculation_exercise(
    payload: GenerateCalculationRequest,
    client: OllamaClient,
) -> dict:
    prompt = build_calculation_prompt(payload)
    validation_errors = []

    for attempt in range(1, MAX_GENERATION_ATTEMPTS + 1):
        raw = await client.generate(prompt, response_format="json")

        try:
            obj = extract_json_best_effort(raw)
            validated = validate_calculation_response(obj)
        except HTTPException as exc:
            if exc.status_code != 422:
                raise
            validation_errors.append(f"Attempt {attempt}: {exc.detail}")
            continue

        return {
            **validated,
            "meta": {
                "provider": "ollama",
                "model": client.model,
                "timeout_sec": client.timeout_sec,
                "prompt_version": "calculation_v1",
                "generation_attempt": attempt,
            },
        }

    last_error = validation_errors[-1] if validation_errors else "No model output was validated."
    raise HTTPException(
        status_code=422,
        detail=(
            "Model did not produce a valid calculation exercise after "
            f"{MAX_GENERATION_ATTEMPTS} attempts. Last error: {last_error}"
        ),
    )


def validate_calculation_response(obj: dict) -> dict:
    validated = {}

    for field in REQUIRED_FIELDS:
        value = obj.get(field)
        if not isinstance(value, str) or not value.strip():
            raise HTTPException(
                status_code=422,
                detail=f"Missing/invalid field '{field}' in model JSON.",
            )
        validated[field] = value.strip()

    task = validated["task"]
    expected_result = validated["expected_result"]
    sample_solution = validated["sample_solution"]

    if any(phrase in task.casefold() for phrase in VAGUE_TASK_PHRASES):
        raise HTTPException(status_code=422, detail="Task is too vague.")

    if len(task) < 40:
        raise HTTPException(status_code=422, detail="Task must be at least 40 characters long.")

    if len(NUMBER_PATTERN.findall(task)) < 2:
        raise HTTPException(status_code=422, detail="Task must contain at least two numeric values.")

    if not EXPECTED_RESULT_PATTERN.fullmatch(expected_result):
        raise HTTPException(
            status_code=422,
            detail="expected_result must contain only a numeric value without unit or explanation.",
        )

    if expected_result not in sample_solution:
        raise HTTPException(status_code=422, detail="sample_solution must contain expected_result.")

    return validated
