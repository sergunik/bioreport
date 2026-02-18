from __future__ import annotations

from typing import Any

import psycopg

from app.config import Settings


def connect(settings: Settings) -> psycopg.Connection[Any]:
    return psycopg.connect(
        host=settings.db_host,
        port=settings.db_port,
        dbname=settings.db_name,
        user=settings.db_user,
        password=settings.db_password,
    )


async def connect_async(settings: Settings) -> psycopg.AsyncConnection[Any]:
    return await psycopg.AsyncConnection.connect(
        host=settings.db_host,
        port=settings.db_port,
        dbname=settings.db_name,
        user=settings.db_user,
        password=settings.db_password,
    )
