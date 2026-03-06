<?php

declare(strict_types=1);

namespace App\DocumentExtraction\DTOs;

final readonly class CreateDocumentExtractionDto
{
    /**
     * @param  array<int, array<string, mixed>>  $observations
     */
    public function __construct(
        public string $documentUuid,
        public ?string $title,
        public ?string $notes,
        public array $observations,
    ) {}

    public static function fromValidated(array $data): self
    {
        return new self(
            documentUuid: (string) $data['document_uuid'],
            title: isset($data['title']) ? (string) $data['title'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            observations: isset($data['observations']) && is_array($data['observations'])
                ? array_values($data['observations'])
                : [],
        );
    }
}
