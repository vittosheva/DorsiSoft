<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages;

use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\Concerns\DispatchesItemsPersistEvent;
use Modules\Sales\Filament\Concerns\SyncsDocumentItemsCount;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;

final class CreateInvoice extends BaseCreateRecord
{
    use DispatchesItemsPersistEvent;
    use SyncsDocumentItemsCount;

    protected static string $resource = InvoiceResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return __(':record f created successfully.', ['record' => self::getResource()::getTitleCaseModelLabel()]);
    }

    protected function getItemsPersistEvent(): string
    {
        return 'invoice-items:persist';
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->danger()
                ->title(__('Sequential number conflict'))
                ->body(__(
                    'The sequential :seq (:est-:ep) is already assigned to another document. Please contact an administrator to correct the sequence before retrying.',
                    [
                        'seq' => $data['sequential_number'] ?? '—',
                        'est' => $data['establishment_code'] ?? '—',
                        'ep' => $data['emission_point_code'] ?? '—',
                    ],
                ))
                ->persistent()
                ->send();

            throw new Halt();
        }
    }
}
