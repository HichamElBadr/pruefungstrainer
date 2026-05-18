from pydantic import BaseModel, Field
from typing import Any, Dict, Optional


class GenerateScanRequest(BaseModel):
    request_id: str = Field(..., description="Eindeutige ID aus Laravel")
    difficulty: str = Field("medium", description="easy|medium|hard")
    language: str = Field("de", description="Ausgabesprache")
    extra_context: Optional[str] = None


class GenerateScanResponse(BaseModel):
    request_id: str
    task: str
    solution: str
    meta: Dict[str, Any]
