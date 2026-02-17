from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from typing import Any
from uuid import UUID


@dataclass
class JobRow:
    id: int
    uploaded_document_id: int
    status: str
    attempts: int
    error_message: str | None
    locked_at: datetime | None
    created_at: datetime
    updated_at: datetime


@dataclass
class DocumentRow:
    id: int
    uuid: UUID
    user_id: int
    storage_disk: str


@dataclass
class ObservationItem:
    biomarker_name: str
    biomarker_code: str
    value: float
    unit: str
    reference_range: str


@dataclass
class NormalizedResult:
    first_name: str
    second_name: str
    dob: str
    language: str
    common: str
    observations: list[ObservationItem]

    def to_json_dict(self) -> dict[str, Any]:
        return {
            "first_name": self.first_name,
            "second_name": self.second_name,
            "DOB": self.dob,
            "language": self.language,
            "common": self.common,
            "observations": [
                {
                    "biomarker_name": o.biomarker_name,
                    "biomarker_code": o.biomarker_code,
                    "value": o.value,
                    "unit": o.unit,
                    "reference_range": o.reference_range,
                }
                for o in self.observations
            ],
        }
