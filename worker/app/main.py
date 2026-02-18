from __future__ import annotations

import asyncio
import logging
import signal
import sys

import structlog

from app.config import Settings
from app.processor import run_loop


def configure_logging() -> None:
    structlog.configure(
        processors=[
            structlog.processors.add_log_level,
            structlog.processors.TimeStamper(fmt="iso"),
            structlog.processors.JSONRenderer(),
        ],
        wrapper_class=structlog.make_filtering_bound_logger(logging.INFO),
        context_class=dict,
        logger_factory=structlog.PrintLoggerFactory(),
    )


def main() -> None:
    configure_logging()
    settings = Settings()
    loop = asyncio.new_event_loop()
    asyncio.set_event_loop(loop)
    task = loop.create_task(run_loop(settings))
    def shutdown() -> None:
        task.cancel()
    loop.add_signal_handler(signal.SIGTERM, shutdown)
    try:
        loop.run_until_complete(task)
    except asyncio.CancelledError:
        structlog.get_logger().info("shutdown", status="graceful")
    finally:
        loop.close()
    sys.exit(0)


if __name__ == "__main__":  # pragma: no cover
    main()
