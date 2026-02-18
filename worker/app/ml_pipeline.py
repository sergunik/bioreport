from __future__ import annotations

from typing import Any

from app.exceptions import MLPipelineError


class MLPipeline:
    def __init__(self, model_name: str) -> None:
        self._model_name = model_name
        self._nlp = self._load_model()

    def _load_model(self) -> Any:
        try:
            import spacy
            return spacy.load(self._model_name)
        except OSError as e:
            raise MLPipelineError(f"Cannot load spaCy model {self._model_name}: {e}") from e

    def run(self, text: str) -> dict[str, Any]:
        if not text or not text.strip():
            raise MLPipelineError("Empty text")
        doc = self._nlp(text[:1_000_000])
        entities = [
            {"text": ent.text, "label": ent.label_, "start": ent.start_char, "end": ent.end_char}
            for ent in doc.ents
        ]
        return {
            "entities": entities,
            "language": doc.lang_ or "en",
            "text_length": len(text),
        }
