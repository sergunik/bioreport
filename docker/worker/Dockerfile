FROM python:3.13-slim

WORKDIR /app

ARG SPACY_MODEL_NAME
ENV SPACY_MODEL_NAME=${SPACY_MODEL_NAME}
ENV PIP_ROOT_USER_ACTION=ignore

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir pip -U

COPY worker/pyproject.toml ./
COPY worker/app/ ./app/
COPY worker/README.md ./

RUN pip install --no-cache-dir . && \
    python -m spacy download "${SPACY_MODEL_NAME}"

USER nobody

CMD ["python", "-m", "app.main"]
