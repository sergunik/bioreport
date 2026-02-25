<?php

declare(strict_types=1);

namespace App\UploadedDocuments\Controllers;

use App\Http\Controllers\AuthenticatedController;
use App\UploadedDocuments\Requests\StoreDocumentRequest;
use App\UploadedDocuments\Resources\DocumentMetadataResource;
use App\UploadedDocuments\Resources\DocumentResource;
use App\UploadedDocuments\Services\UploadedDocumentService;
use App\UploadedDocuments\Services\UploadedDocumentServiceFactory;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles PDF document upload, listing, streaming, and metadata for the authenticated user.
 */
final class DocumentController extends AuthenticatedController
{
    private readonly UploadedDocumentService $documentService;

    public function __construct(
        UploadedDocumentServiceFactory $documentServiceFactory,
    ) {
        parent::__construct();

        $this->documentService = $documentServiceFactory->make($this->user);
    }

    /**
     * Uploads a PDF document. Returns existing document if same hash already uploaded by user.
     */
    #[ScrambleResponse(201, 'Document uploaded', examples: [['uuid' => '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d']])]
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $contents = $file->get();
        if (! is_string($contents)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Uploaded file cannot be read');
        }

        $uuid = $this->documentService->uploadFromContents($contents);

        return response()->json(
            ['uuid' => $uuid],
            Response::HTTP_CREATED,
        );
    }

    /**
     * Lists documents for the current user.
     */
    #[ScrambleResponse(200, 'List of documents', examples: [['data' => [['uuid' => '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d', 'file_size_bytes' => 1024, 'mime_type' => 'application/pdf', 'processed_at' => null, 'created_at' => '2025-02-16T12:00:00.000000Z', 'updated_at' => '2025-02-16T12:00:00.000000Z', 'job_status' => 'pending']]]])]
    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentService->list();

        return response()->json([
            'data' => DocumentResource::collection($documents)->toArray($request),
        ]);
    }

    /**
     * Streams the PDF file. Only the document owner can access.
     */
    public function show(string $uuid): StreamedResponse|JsonResponse
    {
        $document = $this->documentService->getByUuid($uuid);
        if ($document === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $service = $this->documentService;
        $downloadName = $document->uuid.'.pdf';
        try {
            $stream = $service->readStream($document);
        } catch (RuntimeException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, Response::HTTP_OK, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Returns document metadata and processing results for the owner.
     */
    #[ScrambleResponse(200, 'Document metadata and processing results', examples: [['uuid' => '9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d', 'file_size_bytes' => 1024, 'mime_type' => 'application/pdf', 'processed_at' => null, 'created_at' => '2025-02-16T12:00:00.000000Z', 'updated_at' => '2025-02-16T12:00:00.000000Z', 'job_status' => 'pending', 'parsed_result' => null, 'anonymised_result' => null, 'anonymised_artifacts' => null, 'normalized_result' => null]])]
    public function metadata(Request $request, string $uuid): JsonResponse
    {
        $document = $this->documentService->getByUuid($uuid);
        if ($document === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json((new DocumentMetadataResource($document))->toArray($request));
    }
}
