from __future__ import annotations

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_prefix="", case_sensitive=False)

    db_host: str = "localhost"
    db_port: int = 5432
    db_name: str = "bioreport"
    db_user: str = "postgres"
    db_password: str = ""

    poll_interval_seconds: int = 5
    max_attempts: int = 3
    stale_lock_seconds: int = 300
    storage_base_path: str = "/data/uploaded_documents"
    spacy_model_name: str = "en_core_web_sm"
