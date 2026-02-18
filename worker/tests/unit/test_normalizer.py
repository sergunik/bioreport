from __future__ import annotations

from app.normalizer import normalize


def test_normalize_empty_raw() -> None:
    result = normalize({}, "")
    assert result.first_name == ""
    assert result.second_name == ""
    assert result.dob == ""
    assert result.language == "en"
    assert result.common == ""
    assert result.observations == []


def test_normalize_with_person_entity() -> None:
    raw = {"entities": [{"text": "John Doe", "label": "PERSON"}], "language": "en"}
    result = normalize(raw, "Some text")
    assert result.first_name == "John"
    assert result.second_name == "Doe"
    assert result.language == "en"
    assert result.common == "Some text"


def test_normalize_with_date_entity() -> None:
    raw = {"entities": [{"text": "01.01.2000", "label": "DATE"}], "language": "en"}
    result = normalize(raw, "")
    assert result.dob == "01.01.2000"


def test_normalize_to_json_dict() -> None:
    raw = {
        "entities": [
            {"text": "John Doe", "label": "PERSON"},
            {"text": "01.01.2000", "label": "DATE"},
        ],
        "language": "en",
    }
    result = normalize(raw, "Body")
    d = result.to_json_dict()
    assert d["first_name"] == "John"
    assert d["second_name"] == "Doe"
    assert d["DOB"] == "01.01.2000"
    assert d["language"] == "en"
    assert d["common"] == "Body"
    assert "observations" in d


def test_normalize_observations_from_text() -> None:
    raw = {"entities": [], "language": "en"}
    text = "Hemoglobin: 13.4 g/dL [12-16]"
    result = normalize(raw, text)
    assert len(result.observations) >= 1
    obs = result.observations[0]
    assert obs.biomarker_name == "Hemoglobin"
    assert obs.value == 13.4
    assert obs.unit == "g/dL"
    assert obs.reference_range == "12-16"


def test_normalize_skips_too_long_biomarker_name() -> None:
    raw = {"entities": [], "language": "en"}
    text = "VeryLongBiomarkerName " * 10 + ": 12.3 mg/dL"
    result = normalize(raw, text)
    assert result.observations == []
