<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use JsonException;

final class DocumentExtractionStore
{
    public function createQueuedExtraction(
        string $originalFilename,
        string $documentType,
        string $disk,
        string $path,
        ?int $userId = null,
    ): int {
        return (int) DB::table('ocr_processed_documents')->insertGetId([
            'original_filename' => $originalFilename,
            'document_type' => $documentType,
            'extracted_data' => $this->encode([
                'disk' => $disk,
                'path' => $path,
                'raw_text' => null,
                'headers' => [],
                'items' => [],
            ]),
            'confidence_score' => 0,
            'processing_time' => 0,
            'user_id' => $userId,
            'status' => 'queued',
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $record = DB::table('ocr_processed_documents')->where('id', $id)->first();

        if ($record === null) {
            return null;
        }

        return [
            'id' => (int) $record->id,
            'original_filename' => (string) $record->original_filename,
            'document_type' => $record->document_type,
            'extracted_data' => $this->decode($record->extracted_data),
            'confidence_score' => (float) $record->confidence_score,
            'processing_time' => (float) $record->processing_time,
            'user_id' => $record->user_id !== null ? (int) $record->user_id : null,
            'status' => (string) $record->status,
            'error_message' => $record->error_message,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
        ];
    }

    public function markProcessing(int $id): void
    {
        DB::table('ocr_processed_documents')
            ->where('id', $id)
            ->update([
                'status' => 'processing',
                'error_message' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array{headers: array<string, string>, items: array<int, array<string, mixed>>, confidence_score: float}  $parsedPayload
     */
    public function markCompleted(int $id, string $rawText, array $parsedPayload, float $processingTime): void
    {
        $document = $this->find($id);

        if ($document === null) {
            return;
        }

        $data = $document['extracted_data'];
        $data['raw_text'] = $rawText;
        $data['headers'] = $parsedPayload['headers'];
        $data['items'] = $parsedPayload['items'];

        DB::table('ocr_processed_documents')
            ->where('id', $id)
            ->update([
                'status' => 'completed',
                'extracted_data' => $this->encode($data),
                'confidence_score' => min(0.99, max(0, $parsedPayload['confidence_score'])),
                'processing_time' => round($processingTime, 3),
                'error_message' => null,
                'updated_at' => now(),
            ]);
    }

    public function markFailed(int $id, string $errorMessage, float $processingTime): void
    {
        DB::table('ocr_processed_documents')
            ->where('id', $id)
            ->update([
                'status' => 'failed',
                'processing_time' => round($processingTime, 3),
                'error_message' => $errorMessage,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function encode(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '{}';
        }
    }
}
