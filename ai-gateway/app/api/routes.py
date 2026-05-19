from fastapi import APIRouter, Depends
from app.schemas.calculation import GenerateCalculationRequest, GenerateCalculationResponse
from app.schemas.sql import GenerateSqlRequest, GenerateSqlResponse
from app.core.settings import settings
from app.clients.ollama_client import OllamaClient
from app.services.calculation_service import generate_calculation_exercise
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


@router.post("/generate/calculation", response_model=GenerateCalculationResponse)
async def generate_calculation(
    req: GenerateCalculationRequest,
    client: OllamaClient = Depends(get_ollama_client),
):
    result = await generate_calculation_exercise(req, client)

    return GenerateCalculationResponse(
        request_id=req.request_id,
        title=result["title"],
        task=result["task"],
        expected_result=result["expected_result"],
        expected_unit=result["expected_unit"],
        sample_solution=result["sample_solution"],
        meta=result["meta"],
    )
