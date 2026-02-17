from __future__ import annotations

import asyncio
from unittest.mock import AsyncMock, patch

import pytest
from app.config import Settings
from app.processor import run_loop


@pytest.mark.asyncio
async def test_run_loop_exits_on_cancelled() -> None:
    settings = Settings(poll_interval_seconds=1)
    with patch("app.processor.process_one", side_effect=asyncio.CancelledError()):
        await run_loop(settings)


@pytest.mark.asyncio
async def test_run_loop_handles_exception_then_exits_on_cancel() -> None:
    settings = Settings(poll_interval_seconds=1)
    with patch(
        "app.processor.process_one",
        side_effect=[ValueError("err"), asyncio.CancelledError()],
    ):
        with patch("app.processor.asyncio.sleep", new_callable=AsyncMock):
            await run_loop(settings)


@pytest.mark.asyncio
async def test_run_loop_sleeps_when_no_work() -> None:
    settings = Settings(poll_interval_seconds=1)
    with patch("app.processor.process_one", side_effect=[False, asyncio.CancelledError()]):
        with patch("app.processor.asyncio.sleep", new_callable=AsyncMock) as sleep_mock:
            await run_loop(settings)
    sleep_mock.assert_awaited_once_with(1)
