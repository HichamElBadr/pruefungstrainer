from fastapi import APIRouter, Depends
from app.schemas.sql import GenerateSqlRequest, GenerateSqlResponse
from app.core.settings import settings
from app.clients.ollama_client import OllamaClient
from app.services.sql_service import generate_sql_exercise

router = APIRouter()


def get_ollama_client() -> OllamaClient:
    return OllamaClient(
        base_url=settings.OLLAMA_BASE_URL,
        model=settings.OLLAMA_MODEL,
        timeout_sec=settings.OLLAMA_TIMEOUT_SEC,
    )


@router.post("/generate/sql", response_model=GenerateSqlResponse)
async def generate_sql(
    req: GenerateSqlRequest,
    client: OllamaClient = Depends(get_ollama_client),
):
    result = await generate_sql_exercise(req, client)

    return GenerateSqlResponse(
        request_id=req.request_id,
        task=result["task"],
        mysqlstatement=result["mysqlstatement"],
        solution=result["solution"],
        meta=result["meta"],
    )
