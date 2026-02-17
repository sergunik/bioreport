from __future__ import annotations

from unittest.mock import patch

from app.config import Settings
from app.db import connect, get_connection_string


def test_get_connection_string() -> None:
    s = Settings(
        db_host="h",
        db_port=5433,
        db_name="db",
        db_user="u",
        db_password="p",
    )
    url = get_connection_string(s)
    assert url == "postgresql://u:p@h:5433/db"


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
    connect_mock.assert_called_once_with("postgresql://user:pass@host:5432/db")
