from __future__ import annotations

from typing import Any

import psycopg

from app.config import Settings


def get_connection_string(settings: Settings) -> str:
    return (
        f"postgresql://{settings.db_user}:{settings.db_password}"
        f"@{settings.db_host}:{settings.db_port}/{settings.db_name}"
    )


def connect(settings: Settings) -> psycopg.Connection[Any]:
    return psycopg.connect(get_connection_string(settings))


async def connect_async(settings: Settings) -> psycopg.AsyncConnection[Any]:
    return await psycopg.AsyncConnection.connect(get_connection_string(settings))
