from pydantic import BaseModel, Field
from typing import Any, Dict, Optional


class GenerateCalculationRequest(BaseModel):
    request_id: str = Field(..., description="Unique ID from Laravel")
    difficulty: str = Field("medium", description="easy|medium|hard")
    language: str = Field("de", description="Output language")
    topic: str = Field(..., description="Calculation exercise topic")
    extra_context: Optional[str] = None


class GenerateCalculationResponse(BaseModel):
    request_id: str
    title: str
    task: str
    expected_result: str
    expected_unit: str
    sample_solution: str
    meta: Dict[str, Any]
