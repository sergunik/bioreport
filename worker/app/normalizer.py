from __future__ import annotations

import re
from typing import Any

from app.models import NormalizedResult, ObservationItem


def normalize(raw: dict[str, Any], text_snippet: str = "") -> NormalizedResult:
    entities = raw.get("entities") or []
    language = raw.get("language") or "en"
    person_texts = [e["text"] for e in entities if e.get("label") == "PERSON"]
    first_name = person_texts[0].split()[0] if person_texts else ""
    second_name = (
        person_texts[0].split()[-1]
        if person_texts and len(person_texts[0].split()) > 1
        else (person_texts[1] if len(person_texts) > 1 else "")
    )
    date_entities = [e["text"] for e in entities if e.get("label") == "DATE"]
    dob = date_entities[0] if date_entities else ""
    raw_with_snippet = {**raw, "text_snippet": text_snippet[:50000]}
    observations = _extract_observations(raw_with_snippet)
    return NormalizedResult(
        first_name=first_name,
        second_name=second_name,
        dob=dob,
        language=language,
        common=text_snippet[:10000],
        observations=observations,
    )


def _extract_observations(raw: dict[str, Any]) -> list[ObservationItem]:
    observations: list[ObservationItem] = []
    text_snippet = str(raw.get("text_snippet", ""))
    pattern = re.compile(
        r"(?P<name>[A-Za-z][A-Za-z\s\-]+?)\s*[:=]?\s*(?P<value>\d+\.?\d*)\s*(?P<unit>[a-zA-Z/%]+)?\s*(?:\[?(?P<ref>[^\]\n]+)\]?)?",
        re.MULTILINE,
    )
    for m in pattern.finditer(text_snippet):
        name = m.group("name").strip()
        if len(name) > 80:
            continue
        value_str = m.group("value")
        value = float(value_str)
        unit = (m.group("unit") or "").strip() or "—"
        ref = (m.group("ref") or "").strip() or "—"
        code = "".join(w[0] for w in name.split() if w)[:20] if name else "—"
        observations.append(
            ObservationItem(
                biomarker_name=name,
                biomarker_code=code,
                value=value,
                unit=unit,
                reference_range=ref,
            )
        )
    return observations[:500]
