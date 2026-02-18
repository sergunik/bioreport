from __future__ import annotations

from pathlib import Path

import pytest
from app.config import Settings


@pytest.fixture
def settings() -> Settings:
    return Settings(
        db_host="localhost",
        db_port=5432,
        db_name="test",
        db_user="test",
        db_password="test",
        poll_interval_seconds=1,
        max_attempts=3,
        storage_base_path="/tmp/worker_test",
        spacy_model_name="en_core_web_sm",
    )


@pytest.fixture
def temp_storage(tmp_path: Path) -> Path:
    return tmp_path
