from __future__ import annotations

from datetime import datetime
from unittest.mock import AsyncMock, patch
from uuid import uuid4

import pytest
from app.config import Settings
from app.models import DocumentRow, JobRow
from app.processor import process_one


def _job_row(attempts: int = 1) -> JobRow:
    return JobRow(
        id=1,
        uploaded_document_id=10,
        status="processing",
        attempts=attempts,
        error_message=None,
        locked_at=datetime.now(),
        created_at=datetime.now(),
        updated_at=datetime.now(),
    )


def _doc_row() -> DocumentRow:
    return DocumentRow(id=10, uuid=uuid4(), user_id=42, storage_disk="local")


@pytest.mark.asyncio
async def test_process_one_returns_false_when_no_job() -> None:
    settings = Settings(storage_base_path="/tmp")
    mock_conn = AsyncMock()
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=None):
            result = await process_one(settings)
    assert result is False


@pytest.mark.asyncio
async def test_process_one_fails_when_document_not_found() -> None:
    settings = Settings(storage_base_path="/tmp")
    mock_conn = AsyncMock()
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=_job_row()):
            with patch("app.processor.PdfJobRepository") as Repo:
                Repo.return_value.get_document = AsyncMock(return_value=None)
                Repo.return_value.fail_job = AsyncMock()
                result = await process_one(settings)
    assert result is True
    Repo.return_value.fail_job.assert_awaited_once()


@pytest.mark.asyncio
async def test_process_one_fails_when_storage_not_local() -> None:
    settings = Settings(storage_base_path="/tmp")
    mock_conn = AsyncMock()
    doc = _doc_row()
    doc.storage_disk = "s3"
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=_job_row()):
            with patch("app.processor.PdfJobRepository") as Repo:
                Repo.return_value.get_document = AsyncMock(return_value=doc)
                Repo.return_value.fail_job = AsyncMock()
                result = await process_one(settings)
    assert result is True


@pytest.mark.asyncio
async def test_process_one_success_updates_doc_and_job() -> None:
    settings = Settings(storage_base_path="/tmp", spacy_model_name="en_core_web_sm")
    mock_conn = AsyncMock()
    doc = _doc_row()
    job = _job_row()
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=job):
            with patch("app.processor.PdfJobRepository") as Repo:
                Repo.return_value.get_document = AsyncMock(return_value=doc)
                Repo.return_value.complete_job = AsyncMock()
                with patch("app.processor.extract_text_from_pdf", return_value="Hello"):
                    with patch("app.processor.MLPipeline") as ML:
                        ML.return_value.run.return_value = {
                            "entities": [],
                            "language": "en",
                            "text_length": 5,
                        }
                        result = await process_one(settings)
    assert result is True
    Repo.return_value.complete_job.assert_awaited_once()


@pytest.mark.asyncio
async def test_process_one_requeues_on_pdf_error_when_attempts_under_max() -> None:
    from app.exceptions import PDFExtractError
    settings = Settings(storage_base_path="/tmp", max_attempts=3)
    mock_conn = AsyncMock()
    job = _job_row(attempts=1)
    doc = _doc_row()
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=job):
            with patch("app.processor.PdfJobRepository") as Repo:
                Repo.return_value.get_document = AsyncMock(return_value=doc)
                Repo.return_value.requeue_job = AsyncMock()
                with patch(
                    "app.processor.extract_text_from_pdf", side_effect=PDFExtractError("bad")
                ):
                    result = await process_one(settings)
    assert result is True
    Repo.return_value.requeue_job.assert_awaited_once()


@pytest.mark.asyncio
async def test_process_one_fails_on_pdf_error_when_attempts_at_max() -> None:
    from app.exceptions import PDFExtractError
    settings = Settings(storage_base_path="/tmp", max_attempts=3)
    mock_conn = AsyncMock()
    job = _job_row(attempts=3)
    doc = _doc_row()
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=job):
            with patch("app.processor.PdfJobRepository") as Repo:
                Repo.return_value.get_document = AsyncMock(return_value=doc)
                Repo.return_value.fail_job = AsyncMock()
                with patch(
                    "app.processor.extract_text_from_pdf", side_effect=PDFExtractError("bad")
                ):
                    result = await process_one(settings)
    assert result is True
    Repo.return_value.fail_job.assert_awaited_once()


@pytest.mark.asyncio
async def test_process_one_requeues_on_ml_error_when_attempts_under_max() -> None:
    settings = Settings(storage_base_path="/tmp", max_attempts=3, spacy_model_name="en_core_web_sm")
    mock_conn = AsyncMock()
    job = _job_row(attempts=1)
    doc = _doc_row()
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=job):
            with patch("app.processor.PdfJobRepository") as Repo:
                Repo.return_value.get_document = AsyncMock(return_value=doc)
                Repo.return_value.requeue_job = AsyncMock()
                with patch("app.processor.extract_text_from_pdf", return_value="Hi"):
                    with patch(
                        "app.processor.MLPipeline",
                        side_effect=RuntimeError("model error"),
                    ):
                        result = await process_one(settings)
    assert result is True
    Repo.return_value.requeue_job.assert_awaited_once()


@pytest.mark.asyncio
async def test_process_one_storage_error_fails_job() -> None:
    from app.exceptions import StorageError
    settings = Settings(storage_base_path="/tmp")
    mock_conn = AsyncMock()
    doc = _doc_row()
    with patch("app.processor.connect_async", return_value=mock_conn):
        with patch("app.processor.lock_one_job", return_value=_job_row()):
            with patch("app.processor.PdfJobRepository") as Repo:
                Repo.return_value.get_document = AsyncMock(return_value=doc)
                Repo.return_value.fail_job = AsyncMock()
                with patch(
                    "app.processor.resolve_document_path",
                    side_effect=StorageError("traversal"),
                ):
                    result = await process_one(settings)
    assert result is True
    Repo.return_value.fail_job.assert_awaited_once()
