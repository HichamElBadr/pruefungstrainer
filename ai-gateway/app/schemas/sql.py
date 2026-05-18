from pydantic import BaseModel, Field
from typing import Optional, Dict, Any


class GenerateSqlRequest(BaseModel):
    request_id: str = Field(..., description="Eindeutige ID aus Laravel")
    difficulty: str = Field("medium", description="easy|medium|hard")
    topic: Optional[str] = Field(None, description="z.B. JOIN, GROUP BY, Subquery")
    language: str = Field("de", description="Ausgabesprache")
    extra_context: Optional[str] = None


class GenerateSqlResponse(BaseModel):
    request_id: str
    task: str
    mysqlstatement: str
    solution: str
    meta: Dict[str, Any]
