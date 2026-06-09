from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict


GATEWAY_ROOT = Path(__file__).resolve().parents[2]
PROJECT_ROOT = GATEWAY_ROOT.parent


class Settings(BaseSettings):
    OLLAMA_BASE_URL: str = "http://localhost:11434"
    OLLAMA_MODEL: str = "deepseek-r1:7b"
    OLLAMA_TIMEOUT_SEC: int = 120

    model_config = SettingsConfigDict(
        env_file=(PROJECT_ROOT / ".env", GATEWAY_ROOT / ".env"),
        env_file_encoding="utf-8",
        extra="ignore",
    )


settings = Settings()
