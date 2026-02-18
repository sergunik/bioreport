from __future__ import annotations

from pathlib import Path

import pdfplumber

from app.exceptions import PDFExtractError


def extract_text_from_pdf(path: Path) -> str:
    if not path.exists():
        raise PDFExtractError(f"File not found: {path}")
    if not path.is_file():
        raise PDFExtractError(f"Not a file: {path}")
    try:
        parts: list[str] = []
        with pdfplumber.open(path) as pdf:
            for page in pdf.pages:
                text = page.extract_text()
                if text:
                    parts.append(text)
        result = "\n".join(parts).strip()
        if not result:
            raise PDFExtractError("PDF produced no text (possibly image-based)")
        return result
    except PDFExtractError:
        raise
    except OSError as e:
        raise PDFExtractError(f"Cannot read file: {e}") from e
    except Exception as e:
        raise PDFExtractError(f"Invalid or broken PDF: {e}") from e
