from __future__ import annotations

import asyncio
import traceback
from pathlib import Path

import structlog

from app.config import Settings
from app.db import connect_async
from app.exceptions import PDFExtractError, StorageError
from app.job_locking import lock_one_job
from app.ml_pipeline import MLPipeline
from app.normalizer import normalize
from app.pdf_reader import extract_text_from_pdf
from app.repository import PdfJobRepository

logger = structlog.get_logger()


def resolve_document_path(settings: Settings, user_id: int, uuid: str) -> Path:
    base = Path(settings.storage_base_path)
    if not base.is_absolute():
        base = Path.cwd() / base
    base_resolved = base.resolve()
    path = base / str(user_id) / f"{uuid}.pdf"
    resolved = path.resolve()
    try:
        resolved.relative_to(base_resolved)
    except ValueError:
        raise StorageError("Path traversal not allowed") from None
    return resolved


async def process_one(settings: Settings) -> bool:
    conn = await connect_async(settings)
    try:
        job = await lock_one_job(conn, settings.stale_lock_seconds)
        if job is None:
            return False
        job_id = job.id
        document_id = job.uploaded_document_id
        log = logger.bind(job_id=job_id, document_id=document_id)
        log.info("job_locked")
        repo = PdfJobRepository(conn)
        doc = await repo.get_document(document_id)
        if doc is None:
            await repo.fail_job(job_id, "Document not found")
            await conn.commit()
            log.warning("document_not_found")
            return True
        if doc.storage_disk != "local":
            await repo.fail_job(job_id, "Only local storage is supported")
            await conn.commit()
            log.warning("unsupported_storage", storage_disk=doc.storage_disk)
            return True
        try:
            path = resolve_document_path(settings, doc.user_id, str(doc.uuid))
        except StorageError as e:
            await repo.fail_job(job_id, str(e))
            await conn.commit()
            log.warning("storage_error", error_type=type(e).__name__)
            return True
        try:
            text = extract_text_from_pdf(path)
        except PDFExtractError as e:
            err = f"{e!s}\n{traceback.format_exc()}"
            if job.attempts >= settings.max_attempts:
                await repo.fail_job(job_id, err)
                log.warning("job_failed", status="failed", error_type=type(e).__name__)
            else:
                await repo.requeue_job(job_id)
                log.warning("job_requeued", status="pending", error_type=type(e).__name__)
            await conn.commit()
            return True
        try:
            pipeline = MLPipeline(settings.spacy_model_name)
            raw_result = pipeline.run(text)
        except Exception as e:
            err = f"{e!s}\n{traceback.format_exc()}"
            if job.attempts >= settings.max_attempts:
                await repo.fail_job(job_id, err)
                log.warning("job_failed", status="failed", error_type=type(e).__name__)
            else:
                await repo.requeue_job(job_id)
                log.warning("job_requeued", status="pending", error_type=type(e).__name__)
            await conn.commit()
            return True
        try:
            normalized = normalize(raw_result, text_snippet=text)
            await repo.complete_job(
                job_id,
                document_id,
                raw_result,
                normalized.to_json_dict(),
            )
            await conn.commit()
        except Exception as e:
            err = f"{e!s}\n{traceback.format_exc()}"
            if job.attempts >= settings.max_attempts:
                await repo.fail_job(job_id, err)
                log.warning("job_failed", status="failed", error_type=type(e).__name__)
            else:
                await repo.requeue_job(job_id)
                log.warning("job_requeued", status="pending", error_type=type(e).__name__)
            await conn.commit()
            return True
        log.info("job_done", status="done")
        return True
    finally:
        await conn.close()


async def run_loop(settings: Settings) -> None:
    while True:
        try:
            did_work = await process_one(settings)
            if not did_work:
                await asyncio.sleep(settings.poll_interval_seconds)
        except asyncio.CancelledError:
            break
        except Exception as e:
            logger.exception("loop_error", error_type=type(e).__name__)
            await asyncio.sleep(settings.poll_interval_seconds)
