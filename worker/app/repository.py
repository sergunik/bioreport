from __future__ import annotations

import json
from typing import Any

import psycopg
from psycopg.rows import class_row

from app.models import DocumentRow


class PdfJobRepository:
    def __init__(self, conn: psycopg.AsyncConnection[Any]) -> None:
        self._conn = conn

    async def get_document(self, document_id: int) -> DocumentRow | None:
        async with self._conn.cursor(row_factory=class_row(DocumentRow)) as cur:
            await cur.execute(
                """
                SELECT id, uuid, user_id, storage_disk
                FROM uploaded_documents
                WHERE id = %s
                """,
                (document_id,),
            )
            return await cur.fetchone()

    async def complete_job(
        self,
        job_id: int,
        document_id: int,
        ml_raw_result: dict[str, Any],
        ml_normalized_result: dict[str, Any],
    ) -> None:
        raw_json = json.dumps(ml_raw_result)
        norm_json = json.dumps(ml_normalized_result)
        async with self._conn.transaction(), self._conn.cursor() as cur:
            await cur.execute(
                """
                UPDATE uploaded_documents
                SET ml_raw_result = %s::jsonb, ml_normalized_result = %s::jsonb,
                    processed_at = now(), updated_at = now()
                WHERE id = %s
                """,
                (raw_json, norm_json, document_id),
            )
            await cur.execute(
                """
                UPDATE pdf_jobs
                SET status = 'done', updated_at = now()
                WHERE id = %s
                """,
                (job_id,),
            )

    async def fail_job(self, job_id: int, error_message: str) -> None:
        truncated = error_message[:4096] if len(error_message) > 4096 else error_message
        async with self._conn.cursor() as cur:
            await cur.execute(
                """
                UPDATE pdf_jobs
                SET status = 'failed', error_message = %s, updated_at = now()
                WHERE id = %s
                """,
                (truncated, job_id),
            )

    async def requeue_job(self, job_id: int) -> None:
        async with self._conn.cursor() as cur:
            await cur.execute(
                """
                UPDATE pdf_jobs
                SET status = 'pending', error_message = NULL, locked_at = NULL, updated_at = now()
                WHERE id = %s
                """,
                (job_id,),
            )
