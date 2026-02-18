from __future__ import annotations

from datetime import datetime
from unittest.mock import AsyncMock, MagicMock

import pytest
from app.job_locking import lock_one_job
from app.models import JobRow


def test_job_row_has_required_attributes() -> None:
    now = datetime.now()
    row = JobRow(
        id=1,
        uploaded_document_id=2,
        status="pending",
        attempts=0,
        error_message=None,
        locked_at=None,
        created_at=now,
        updated_at=now,
    )
    assert row.id == 1
    assert row.uploaded_document_id == 2
    assert row.status == "pending"
    assert row.attempts == 0


@pytest.mark.asyncio
async def test_lock_one_job_returns_none_when_no_candidate() -> None:
    conn = MagicMock()
    cur = AsyncMock()
    cur.fetchone.return_value = None
    cursor_ctx = MagicMock()
    cursor_ctx.__aenter__ = AsyncMock(return_value=cur)
    cursor_ctx.__aexit__ = AsyncMock(return_value=None)
    tx_ctx = MagicMock()
    tx_ctx.__aenter__ = AsyncMock(return_value=None)
    tx_ctx.__aexit__ = AsyncMock(return_value=None)
    conn.cursor.return_value = cursor_ctx
    conn.transaction.return_value = tx_ctx

    row = await lock_one_job(conn, stale_lock_seconds=300)

    assert row is None
    cur.execute.assert_awaited_once()


@pytest.mark.asyncio
async def test_lock_one_job_returns_updated_row_with_incremented_attempts() -> None:
    now = datetime.now()
    row = JobRow(
        id=1,
        uploaded_document_id=2,
        status="processing",
        attempts=3,
        error_message=None,
        locked_at=now,
        created_at=now,
        updated_at=now,
    )
    conn = MagicMock()
    cur = AsyncMock()
    cur.fetchone.return_value = row
    cursor_ctx = MagicMock()
    cursor_ctx.__aenter__ = AsyncMock(return_value=cur)
    cursor_ctx.__aexit__ = AsyncMock(return_value=None)
    tx_ctx = MagicMock()
    tx_ctx.__aenter__ = AsyncMock(return_value=None)
    tx_ctx.__aexit__ = AsyncMock(return_value=None)
    conn.cursor.return_value = cursor_ctx
    conn.transaction.return_value = tx_ctx

    got = await lock_one_job(conn, stale_lock_seconds=120)

    assert got is not None
    assert got.attempts == 3
    execute_args = cur.execute.call_args
    assert execute_args is not None
    assert execute_args[0][1] == (120,)
