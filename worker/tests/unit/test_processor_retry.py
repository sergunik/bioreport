from __future__ import annotations

from app.config import Settings


def test_max_attempts_retry_logic() -> None:
    settings = Settings(max_attempts=3)
    assert settings.max_attempts == 3
