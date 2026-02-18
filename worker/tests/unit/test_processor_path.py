from __future__ import annotations

from pathlib import Path

import pytest
from app.config import Settings
from app.processor import resolve_document_path


def test_resolve_document_path_joins_base_user_uuid() -> None:
    settings = Settings(storage_base_path="/data/docs")
    path = resolve_document_path(settings, 42, "abc-123-uuid")
    assert path == Path("/data/docs/42/abc-123-uuid.pdf")


def test_resolve_document_path_prevents_traversal() -> None:
    settings = Settings(storage_base_path="/data/docs")
    with pytest.raises(Exception, match="Path traversal"):
        resolve_document_path(settings, 1, "../../../etc/passwd")
