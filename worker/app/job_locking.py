from __future__ import annotations

from typing import Any

import psycopg
from psycopg.rows import class_row

from app.models import JobRow


async def lock_one_job(
    conn: psycopg.AsyncConnection[Any], stale_lock_seconds: int
) -> JobRow | None:
    async with conn.transaction(), conn.cursor(row_factory=class_row(JobRow)) as cur:
        await cur.execute(
            """
            WITH candidate AS (
                SELECT id
                FROM pdf_jobs
                WHERE status = 'pending'
                   OR (
                        status = 'processing'
                    AND locked_at IS NOT NULL
                    AND locked_at < now() - (%s * interval '1 second')
                   )
                ORDER BY id
                FOR UPDATE SKIP LOCKED
                LIMIT 1
            )
            UPDATE pdf_jobs AS j
            SET status = 'processing',
                locked_at = now(),
                attempts = j.attempts + 1,
                error_message = NULL,
                updated_at = now()
            FROM candidate
            WHERE j.id = candidate.id
            RETURNING j.id, j.uploaded_document_id, j.status, j.attempts,
                      j.error_message, j.locked_at, j.created_at, j.updated_at
            """,
            (stale_lock_seconds,),
        )
        row = await cur.fetchone()
        return row
