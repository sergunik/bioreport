FROM python:3.13-slim

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir pip -U

COPY worker/pyproject.toml ./
RUN pip install --no-cache-dir . && \
    python -m spacy download en_core_web_sm

COPY worker/app/ ./app/
COPY worker/README.md ./

USER nobody

CMD ["python", "-m", "app.main"]
