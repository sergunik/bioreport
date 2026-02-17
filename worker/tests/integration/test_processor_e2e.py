from __future__ import annotations

from urllib.parse import urlparse

import pytest
from app.config import Settings
from app.db import get_connection_string
from app.processor import process_one
from testcontainers.postgres import PostgresContainer


def _settings_from_url(url: str) -> Settings:
    u = urlparse(url)
    path = u.path.lstrip("/").split("?")[0] or "test"
    return Settings(
        db_host=u.hostname or "localhost",
        db_port=u.port or 5432,
        db_name=path,
        db_user=u.username or "test",
        db_password=u.password or "test",
        storage_base_path="/tmp",
        spacy_model_name="en_core_web_sm",
    )


SCHEMA_SQL = """
CREATE TABLE IF NOT EXISTS uploaded_documents (
    id bigserial PRIMARY KEY,
    uuid uuid NOT NULL,
    user_id bigint NOT NULL,
    storage_disk varchar(20) NOT NULL,
    file_size_bytes bigint,
    mime_type varchar(50),
    file_hash_sha256 char(64),
    ml_raw_result jsonb,
    ml_normalized_result jsonb,
    processed_at timestamp,
    created_at timestamp,
    updated_at timestamp
);
CREATE TABLE IF NOT EXISTS pdf_jobs (
    id bigserial PRIMARY KEY,
    uploaded_document_id bigint NOT NULL,
    status varchar(20) DEFAULT 'pending',
    attempts int DEFAULT 0,
    error_message text,
    locked_at timestamp,
    created_at timestamp,
    updated_at timestamp
);
"""


@pytest.fixture(scope="module")
def postgres_url() -> str:
    with PostgresContainer("postgres:18-alpine") as postgres:
        url = postgres.get_connection_url()
        if "?" in url:
            url = url.split("?")[0]
        yield url


@pytest.fixture(scope="module")
def db_settings(postgres_url: str) -> Settings:
    return _settings_from_url(postgres_url)


@pytest.fixture(scope="module")
def init_schema(db_settings: Settings) -> None:
    import psycopg
    conn = psycopg.connect(get_connection_string(db_settings))
    try:
        with conn.cursor() as cur:
            for stmt in SCHEMA_SQL.strip().split(";"):
                if stmt.strip():
                    cur.execute(stmt)
        conn.commit()
    finally:
        conn.close()


@pytest.mark.asyncio
async def test_process_one_returns_false_when_no_jobs(
    db_settings: Settings, init_schema: None
) -> None:
    did_work = await process_one(db_settings)
    assert did_work is False
