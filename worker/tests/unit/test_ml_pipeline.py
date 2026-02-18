from __future__ import annotations

from unittest.mock import MagicMock, patch

import pytest
from app.exceptions import MLPipelineError
from app.ml_pipeline import MLPipeline


def test_ml_pipeline_run_returns_entities() -> None:
    mock_doc = MagicMock()
    mock_doc.ents = []
    mock_doc.lang_ = "en"
    mock_nlp = MagicMock(return_value=mock_doc)
    with patch("spacy.load", return_value=mock_nlp):
        pipeline = MLPipeline("en_core_web_sm")
        result = pipeline.run("Hello world")
    assert "entities" in result
    assert result["language"] == "en"
    assert result["text_length"] == 11


def test_ml_pipeline_run_raises_on_empty_text() -> None:
    with patch("spacy.load", return_value=MagicMock()):
        pipeline = MLPipeline("en_core_web_sm")
        with pytest.raises(MLPipelineError, match="Empty text"):
            pipeline.run("")


def test_ml_pipeline_run_raises_on_whitespace_only() -> None:
    with patch("spacy.load", return_value=MagicMock()):
        pipeline = MLPipeline("en_core_web_sm")
        with pytest.raises(MLPipelineError, match="Empty text"):
            pipeline.run("   ")


def test_ml_pipeline_load_error_raises_ml_error() -> None:
    with patch("spacy.load", side_effect=OSError("missing model")):
        with pytest.raises(MLPipelineError, match="Cannot load spaCy model"):
            MLPipeline("en_core_web_sm")
