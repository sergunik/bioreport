from __future__ import annotations

import asyncio
from unittest.mock import MagicMock, patch

import pytest
from app.main import configure_logging, main


def test_configure_logging_runs() -> None:
    configure_logging()


def test_main_exits_gracefully_on_cancel() -> None:
    loop = MagicMock()
    task = MagicMock()
    loop.create_task.return_value = task
    handlers: dict[str, object] = {}

    def _add_signal_handler(sig: object, cb: object) -> None:
        handlers["shutdown"] = cb

    def _run_until_complete(_task: object) -> None:
        shutdown = handlers.get("shutdown")
        assert shutdown is not None
        shutdown()
        raise asyncio.CancelledError()

    loop.add_signal_handler.side_effect = _add_signal_handler
    loop.run_until_complete.side_effect = _run_until_complete
    with (
        patch("app.main.configure_logging"),
        patch("app.main.Settings"),
        patch("app.main.run_loop", new=MagicMock(return_value=None)),
        patch("app.main.asyncio.new_event_loop", return_value=loop),
        patch("app.main.asyncio.set_event_loop"),
    ):
        with pytest.raises(SystemExit):
            main()
    task.cancel.assert_called_once()
