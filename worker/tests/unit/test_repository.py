from __future__ import annotations

from unittest.mock import AsyncMock, MagicMock

import pytest
from app.repository import PdfJobRepository


@pytest.mark.asyncio
async def test_complete_job_updates_both_tables() -> None:
    conn = MagicMock()
    cur = AsyncMock()
    ctx_cursor = MagicMock()
    ctx_cursor.__aenter__ = AsyncMock(return_value=cur)
    ctx_cursor.__aexit__ = AsyncMock(return_value=None)
    conn.cursor.return_value = ctx_cursor
    ctx_tx = MagicMock()
    ctx_tx.__aenter__ = AsyncMock(return_value=None)
    ctx_tx.__aexit__ = AsyncMock(return_value=None)
    conn.transaction.return_value = ctx_tx
    repo = PdfJobRepository(conn)
    await repo.complete_job(
        1,
        10,
        {"entities": []},
        {
            "first_name": "J",
            "second_name": "D",
            "DOB": "",
            "language": "en",
            "common": "",
            "observations": [],
        },
    )
    assert cur.execute.await_count >= 2


@pytest.mark.asyncio
async def test_fail_job_truncates_message() -> None:
    conn = MagicMock()
    cur = AsyncMock()
    ctx_cursor = MagicMock()
    ctx_cursor.__aenter__ = AsyncMock(return_value=cur)
    ctx_cursor.__aexit__ = AsyncMock(return_value=None)
    conn.cursor.return_value = ctx_cursor
    repo = PdfJobRepository(conn)
    long_msg = "x" * 5000
    await repo.fail_job(1, long_msg)
    call_args = cur.execute.call_args
    assert call_args is not None
    assert len(call_args[0][1]) <= 4096


@pytest.mark.asyncio
async def test_get_document_reads_row() -> None:
    conn = MagicMock()
    cur = AsyncMock()
    cur.fetchone.return_value = {"id": 10}
    ctx_cursor = MagicMock()
    ctx_cursor.__aenter__ = AsyncMock(return_value=cur)
    ctx_cursor.__aexit__ = AsyncMock(return_value=None)
    conn.cursor.return_value = ctx_cursor
    repo = PdfJobRepository(conn)
    result = await repo.get_document(10)
    assert result == {"id": 10}
    cur.execute.assert_awaited_once()


@pytest.mark.asyncio
async def test_requeue_job_updates_status_to_pending() -> None:
    conn = MagicMock()
    cur = AsyncMock()
    ctx_cursor = MagicMock()
    ctx_cursor.__aenter__ = AsyncMock(return_value=cur)
    ctx_cursor.__aexit__ = AsyncMock(return_value=None)
    conn.cursor.return_value = ctx_cursor
    repo = PdfJobRepository(conn)
    await repo.requeue_job(11)
    execute_args = cur.execute.call_args
    assert execute_args is not None
    assert execute_args[0][1] == (11,)
