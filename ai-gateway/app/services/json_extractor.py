from __future__ import annotations

import json
import re
from typing import Any, Dict

from fastapi import HTTPException


def extract_json_best_effort(text: str) -> Dict[str, Any]:
    """
    Extrahiert JSON robust aus Modell-Output:
    1) ```json ... ``` Codeblock
    2) erster { bis letzter } im Text
    3) direkter JSON-Parse

    Wirft HTTPException 422 bei ungültigem/leerem Output.
    """
    if not text or not text.strip():
        raise HTTPException(status_code=422, detail="Empty response from model.")

    s = text.strip()

    # 1) Codeblock
    m = re.search(r"```(?:json)?\s*(\{.*?\})\s*```", s, flags=re.DOTALL | re.IGNORECASE)
    if m:
        candidate = m.group(1).strip()
        return _loads_or_422(candidate, "Invalid JSON inside code block")

    # 2) braces fallback
    start = s.find("{")
    end = s.rfind("}")
    if start != -1 and end != -1 and end > start:
        candidate = s[start:end + 1].strip()
        return _loads_or_422(candidate, "Invalid JSON extracted by braces")

    # 3) direct
    return _loads_or_422(s, "Invalid JSON from model")


def _loads_or_422(candidate: str, prefix: str) -> Dict[str, Any]:
    try:
        obj = json.loads(candidate)
    except json.JSONDecodeError as e:
        snippet = candidate[:800]
        raise HTTPException(status_code=422, detail=f"{prefix}: {e}; raw={snippet}")

    if not isinstance(obj, dict):
        raise HTTPException(status_code=422, detail=f"{prefix}: JSON root must be object.")
    return obj
