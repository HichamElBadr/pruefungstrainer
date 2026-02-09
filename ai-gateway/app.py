from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
import httpx

app = FastAPI(title="Prüfungstrainer AI Gateway")


# ----- Schemas -----
class GenerateSqlRequest(BaseModel):
    request_id: str = Field(..., description="Eindeutige ID aus Laravel")
    difficulty: str = Field("medium", description="easy|medium|hard")
    topic: str | None = Field(None, description="z.B. JOIN, GROUP BY, Subquery")
    language: str = Field("de", description="Ausgabesprache")
    # optional: wenn du später mehr Kontext schicken willst
    extra_context: str | None = None


class GenerateSqlResponse(BaseModel):
    request_id: str
    task: str
    mysqlstatement: str
    solution: str
    meta: dict


# ----- Config (erstmal hardcoded, später .env) -----
OLLAMA_BASE_URL = "http://localhost:11434"
OLLAMA_MODEL = "deepseek-r1:32b"  # ggf. anpassen: z.B. "mistral:7b" je nach deinem Ollama-Namen
OLLAMA_TIMEOUT_SEC = 120


# ----- Helpers -----
def build_sql_prompt(payload: GenerateSqlRequest) -> str:
    # Minimaler Prompt, später in Prompt-Dateien auslagern
    topic_line = f"Thema: {payload.topic}\n" if payload.topic else ""
    return f"""
You are a professional exam task generator for IT apprentices (Germany).
Return ONLY valid JSON. No markdown. No extra text.

Generate one SQL exercise in German. Difficulty: {payload.difficulty}.
{topic_line}
The JSON schema MUST be exactly:

{{
  "task": "string (German task description)",
  "mysqlstatement": "string (CREATE TABLE + INSERT sample data for the task)",
  "solution": "string (the correct SQL query/queries)"
}}

Rules:
- Use MySQL syntax.
- mysqlstatement must be executable (create tables + insert data).
- solution must solve the task using the provided schema/data.
- Keep it realistic (2-4 tables is fine).
""".strip()


async def ollama_generate(prompt: str) -> str:
    url = f"{OLLAMA_BASE_URL}/api/generate"
    body = {
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False,
        # Optional: "options": {"temperature": 0.2}
    }

    async with httpx.AsyncClient(timeout=OLLAMA_TIMEOUT_SEC) as client:
        resp = await client.post(url, json=body)
        if resp.status_code != 200:
            raise HTTPException(status_code=502, detail=f"Ollama error: {resp.text}")
        data = resp.json()
        # Ollama liefert typischerweise {"response": "...", ...}
        return data.get("response", "")


def extract_json_best_effort(text: str) -> dict:
    """
    Extrahiert JSON auch dann, wenn das Modell es in ```json ... ``` oder Text einbettet.
    """
    import json
    import re

    if not text:
        raise HTTPException(status_code=422, detail="Empty response from model.")

    s = text.strip()

    # 1) ```json ... ``` oder ``` ... ``` Codeblock
    m = re.search(r"```(?:json)?\s*(\{.*?\})\s*```", s, flags=re.DOTALL | re.IGNORECASE)
    if m:
        candidate = m.group(1).strip()
        try:
            return json.loads(candidate)
        except json.JSONDecodeError as e:
            raise HTTPException(
                status_code=422,
                detail=f"Invalid JSON inside code block: {e}; raw={candidate[:800]}"
            )

    # 2) Fallback: erstes { bis letztes } (wenn Modell drumherum labert)
    start = s.find("{")
    end = s.rfind("}")
    if start != -1 and end != -1 and end > start:
        candidate = s[start:end + 1].strip()
        try:
            return json.loads(candidate)
        except json.JSONDecodeError as e:
            raise HTTPException(
                status_code=422,
                detail=f"Invalid JSON extracted by braces: {e}; raw={candidate[:800]}"
            )

    # 3) Letzter Versuch: direkt parsen
    try:
        return json.loads(s)
    except json.JSONDecodeError as e:
        raise HTTPException(
            status_code=422,
            detail=f"Invalid JSON from model: {e}; raw={s[:800]}"
        )



# ----- Routes -----
@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/generate/sql", response_model=GenerateSqlResponse)
async def generate_sql(req: GenerateSqlRequest):
    prompt = build_sql_prompt(req)
    raw = await ollama_generate(prompt)
    obj = extract_json_best_effort(raw)

    # Pflichtfelder prüfen (minimal)
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
            "model": OLLAMA_MODEL,
            "timeout_sec": OLLAMA_TIMEOUT_SEC,
        },
    )
