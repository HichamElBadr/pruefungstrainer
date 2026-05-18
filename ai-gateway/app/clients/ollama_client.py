from typing import Optional

import httpx
from fastapi import HTTPException


class OllamaClient:
    def __init__(self, base_url: str, model: str, timeout_sec: int = 120):
        self.base_url = base_url.rstrip("/")
        self.model = model
        self.timeout_sec = timeout_sec

    async def generate(self, prompt: str, response_format: Optional[str] = None) -> str:
        url = f"{self.base_url}/api/generate"
        body = {
            "model": self.model,
            "prompt": prompt,
            "stream": False,
        }
        if response_format:
            body["format"] = response_format

        async with httpx.AsyncClient(timeout=self.timeout_sec) as client:
            resp = await client.post(url, json=body)
            if resp.status_code != 200:
                raise HTTPException(status_code=502, detail=f"Ollama error: {resp.text}")
            data = resp.json()
            return data.get("response", "")
