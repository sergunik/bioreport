from __future__ import annotations

from unittest.mock import patch

from app.config import Settings
from app.db import connect


def test_connect_calls_psycopg_connect() -> None:
    s = Settings(
        db_host="host",
        db_port=5432,
        db_name="db",
        db_user="user",
        db_password="pass",
    )
    with patch("app.db.psycopg.connect") as connect_mock:
        connect(s)
    connect_mock.assert_called_once_with(
        host="host",
        port=5432,
        dbname="db",
        user="user",
        password="pass",
    )
