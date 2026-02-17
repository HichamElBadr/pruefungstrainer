from fastapi import APIRouter, Depends, FastAPI, HTTPException

from app.schemas.sql import GenerateSqlRequest, GenerateSqlResponse
from app.clients.ollama_client import OllamaClient
from app.services.sql_service import build_sql_prompt, extract_json_best_effort

router = APIRouter()


def get_ollama_client() -> OllamaClient:
    # erstmal hardcoded nachher auf env
    return OllamaClient(
        base_url="http://localhost:11434",
        model="deepseek-r1:32b",
        timeout_sec=120,
    )


@router.get("/health")
def health():
    return {"status": "ok"}


@router.post("/generate/sql", response_model=GenerateSqlResponse)
async def generate_sql(req: GenerateSqlRequest, client: OllamaClient = Depends(get_ollama_client)):
    prompt = build_sql_prompt(req)
    raw = await client.generate(prompt)
    obj = extract_json_best_effort(raw)

    for k in ("task", "mysqlstatement", "solution"):
        if k not in obj or not isinstance(obj[k], str) or not obj[k].strip():
            raise HTTPException(status_code=422, detail=f"Missing/invalid field '{k}' in model JSON.")

    return GenerateSqlResponse(
        request_id=req.request_id,
        task=obj["task"].strip(),
        mysqlstatement=obj["mysqlstatement"].strip(),
        solution=obj["solution"].strip(),
        meta={
            "provider": "ollama",
            "model": client.model,
            "timeout_sec": client.timeout_sec,
            "prompt_version": "sql_v1",
        },
    )
