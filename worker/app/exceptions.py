from __future__ import annotations


class WorkerError(Exception):
    pass


class JobLockError(WorkerError):
    pass


class DocumentNotFoundError(WorkerError):
    pass


class StorageError(WorkerError):
    pass


class PDFExtractError(WorkerError):
    pass


class MLPipelineError(WorkerError):
    pass
