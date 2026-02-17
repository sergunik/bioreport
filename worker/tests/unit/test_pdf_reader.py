from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock, patch

import pytest
from app.exceptions import PDFExtractError
from app.pdf_reader import extract_text_from_pdf


def test_extract_text_returns_joined_text() -> None:
    mock_page = MagicMock()
    mock_page.extract_text.return_value = "Page one"
    mock_pdf = MagicMock()
    mock_pdf.pages = [mock_page]
    mock_cm = MagicMock()
    mock_cm.__enter__ = MagicMock(return_value=mock_pdf)
    mock_cm.__exit__ = MagicMock(return_value=False)
    with patch("app.pdf_reader.pdfplumber.open", return_value=mock_cm):
        with patch("app.pdf_reader.Path.exists", return_value=True):
            with patch("app.pdf_reader.Path.is_file", return_value=True):
                result = extract_text_from_pdf(Path("/fake/file.pdf"))
    assert result == "Page one"


def test_extract_text_joins_multiple_pages() -> None:
    mock_pdf = MagicMock()
    mock_pdf.pages = [
        MagicMock(extract_text=MagicMock(return_value="A")),
        MagicMock(extract_text=MagicMock(return_value="B")),
    ]
    mock_cm = MagicMock()
    mock_cm.__enter__ = MagicMock(return_value=mock_pdf)
    mock_cm.__exit__ = MagicMock(return_value=False)
    with patch("app.pdf_reader.pdfplumber.open", return_value=mock_cm):
        with patch("app.pdf_reader.Path.exists", return_value=True):
            with patch("app.pdf_reader.Path.is_file", return_value=True):
                result = extract_text_from_pdf(Path("/fake/file.pdf"))
    assert result == "A\nB"


def test_extract_text_raises_when_empty() -> None:
    mock_pdf = MagicMock()
    mock_pdf.pages = [MagicMock(extract_text=MagicMock(return_value=None))]
    mock_cm = MagicMock()
    mock_cm.__enter__ = MagicMock(return_value=mock_pdf)
    mock_cm.__exit__ = MagicMock(return_value=False)
    with patch("app.pdf_reader.pdfplumber.open", return_value=mock_cm):
        with patch("app.pdf_reader.Path.exists", return_value=True):
            with patch("app.pdf_reader.Path.is_file", return_value=True):
                with pytest.raises(PDFExtractError, match="no text"):
                    extract_text_from_pdf(Path("/fake/file.pdf"))


def test_extract_text_raises_when_file_not_found() -> None:
    with patch("app.pdf_reader.Path.exists", return_value=False):
        with pytest.raises(PDFExtractError, match="File not found"):
            extract_text_from_pdf(Path("/fake/file.pdf"))


def test_extract_text_raises_when_not_file() -> None:
    with patch("app.pdf_reader.Path.exists", return_value=True):
        with patch("app.pdf_reader.Path.is_file", return_value=False):
            with pytest.raises(PDFExtractError, match="Not a file"):
                extract_text_from_pdf(Path("/fake/file.pdf"))


def test_extract_text_wraps_os_error() -> None:
    with patch("app.pdf_reader.Path.exists", return_value=True):
        with patch("app.pdf_reader.Path.is_file", return_value=True):
            with patch("app.pdf_reader.pdfplumber.open", side_effect=OSError("boom")):
                with pytest.raises(PDFExtractError, match="Cannot read file"):
                    extract_text_from_pdf(Path("/fake/file.pdf"))


def test_extract_text_wraps_unexpected_error() -> None:
    with patch("app.pdf_reader.Path.exists", return_value=True):
        with patch("app.pdf_reader.Path.is_file", return_value=True):
            with patch("app.pdf_reader.pdfplumber.open", side_effect=RuntimeError("broken")):
                with pytest.raises(PDFExtractError, match="Invalid or broken PDF"):
                    extract_text_from_pdf(Path("/fake/file.pdf"))
