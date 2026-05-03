<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Core\Contracts\GeneratesPdf;
use Modules\Core\Jobs\SendDocumentEmail;
use ToneGabes\Filament\Icons\Enums\Phosphor;

final class SendDocumentEmailAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Send email'))
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Send email') : null)
            ->icon(Phosphor::Envelope)
            // ->color('info')
            ->modalHeading(__('Send document by email'))
            ->modalSubmitActionLabel(__('Send'))
            ->modalWidth(Width::ExtraLarge)
            ->fillForm(fn (Model $record): array => $this->defaultFormData($record))
            ->schema([
                Select::make('to')
                    ->label(__('Recipients'))
                    ->multiple()
                    ->required()

                    ->options(fn (Model $record): array => $this->getEmailOptions($record))
                    ->default(fn (Model $record): array => $this->getDefaultEmails($record)),

                TextInput::make('subject')
                    ->required()
                    ->maxLength(255),

                Textarea::make('body')
                    ->required()
                    ->rows(7)
                    ->maxLength(5000),
            ])
            ->action(function (Model $record, array $data): void {
                if (! $record instanceof GeneratesPdf) {
                    Notification::make()
                        ->title(__('This document does not support PDF generation.'))
                        ->danger()
                        ->send();

                    return;
                }

                $sender = $this->resolveSenderFromTenant();

                if (blank($sender['from_email'])) {
                    throw ValidationException::withMessages([
                        'to' => 'The active company does not have an email configured.',
                    ]);
                }

                $payload = [
                    'from_email' => $sender['from_email'],
                    'from_name' => $sender['from_name'],
                    'to' => (array) ($data['to'] ?? []),
                    'cc' => $this->parseEmails($data['cc'] ?? null),
                    'bcc' => $this->parseEmails($data['bcc'] ?? null),
                    'subject' => (string) $data['subject'],
                    'body' => (string) $data['body'],
                ];

                if ($payload['to'] === []) {
                    throw ValidationException::withMessages([
                        'to' => __('At least one recipient email is required.'),
                    ]);
                }

                SendDocumentEmail::dispatch(
                    modelClass: $record::class,
                    modelId: (int) $record->getKey(),
                    userId: (int) Auth::id(),
                    tenantId: (string) (filament()->getTenant()?->ruc ?? 'default'),
                    payload: $payload,
                );

                Notification::make()
                    ->title(__('Email queued'))
                    ->body(__('You will be notified once the document email is processed.'))
                    ->info()
                    ->send();
            })
            ->requiresConfirmation();
    }

    public static function getDefaultName(): ?string
    {
        return 'send_document_email';
    }

    /**
     * Get email options from customer_email array.
     *
     * @return array<string, string>
     */
    private function getEmailOptions(Model $record): array
    {
        $emails = $this->parseCustomerEmails($record);
        if (empty($emails)) {
            return [];
        }

        // Return as key-value pairs (email => email for display)
        return array_combine($emails, $emails) ?: [];
    }

    /**
     * Get default selected emails (all available).
     *
     * @return array<int, string>
     */
    private function getDefaultEmails(Model $record): array
    {
        $emails = $this->parseCustomerEmails($record);

        if (count($emails) === 1) {
            return $emails;
        }

        return [];
    }

    /**
     * Parse customer emails - tries BusinessPartner first, then falls back to customer_email field.
     *
     * @return array<int, string>
     */
    private function parseCustomerEmails(Model $record): array
    {
        // Try to get emails from the related BusinessPartner first
        if (method_exists($record, 'businessPartner') && $record->businessPartner) {
            $partnerEmails = $record->businessPartner->email ?? null;
            if (! blank($partnerEmails)) {
                return $this->normalizeEmails($partnerEmails);
            }
        }

        // Fall back to customer_email field on the record
        return $this->normalizeEmails($record->customer_email ?? null);
    }

    /**
     * Normalize emails - handles array, JSON string, and double-encoded JSON.
     *
     * @return array<int, string>
     */
    private function normalizeEmails(mixed $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        // If it's already an array, use it directly
        if (is_array($raw)) {
            return array_values(array_filter(array_map(
                fn (mixed $email): string => mb_strtolower(mb_trim((string) $email)),
                $raw,
            )));
        }

        // Handle string (JSON or double-encoded JSON)
        if (is_string($raw)) {
            // Remove outer quotes and unescape backslashes (double-encoding fix)
            $trimmed = mb_trim($raw, '"');
            $unescaped = stripslashes($trimmed);

            // Try to decode as JSON
            $decoded = json_decode($unescaped, associative: true);
            if (is_array($decoded) && ! empty($decoded)) {
                return array_values(array_filter(array_map(
                    fn (mixed $email): string => mb_strtolower(mb_trim((string) $email)),
                    $decoded,
                )));
            }
        }

        return [];
    }

    private function defaultFormData(Model $record): array
    {
        $documentType = method_exists($record, 'getPdfFileType')
            ? (string) $record->getPdfFileType()->getLabel()
            : (string) class_basename($record);

        return [
            'to' => $this->getDefaultEmails($record),
            'cc' => null,
            'bcc' => null,
            'subject' => __(':document :code', [
                'document' => $documentType,
                'code' => (string) $record->code,
            ]),
            'body' => __('Please find attached the PDF for document :code :company_name.', [
                'code' => (string) $record->code,
                'company_name' => filament()->getTenant()?->legal_name ?? filament()->getTenant()?->trade_name ?? '',
            ]),
        ];
    }

    /**
     * @return array{from_email: string, from_name: string|null}
     */
    private function resolveSenderFromTenant(): array
    {
        $tenant = filament()->getTenant();

        $fromEmail = $tenant?->email;
        $fromName = $tenant?->trade_name ?: $tenant?->legal_name;

        return [
            'from_email' => filled($fromEmail) ? (string) $fromEmail : '',
            'from_name' => filled($fromName) ? (string) $fromName : null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function parseEmails(mixed $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        $parts = preg_split('/[;,]+/', (string) $raw) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $email = mb_strtolower(mb_trim($part));

            if ($email === '') {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'to' => __('Invalid email address: :email', ['email' => $email]),
                ]);
            }

            $emails[] = $email;
        }

        return array_values(array_unique($emails));
    }
}
