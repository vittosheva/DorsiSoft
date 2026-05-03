<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Company;
use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\Sales\Jobs\SendElectronicDocumentNotificationJob;
use Modules\Sri\Contracts\HasElectronicBilling;

final class SendElectronicDocumentEmailService
{
    public function dispatch(HasElectronicBilling&Model $document, Company $company): void
    {
        if (blank($company->email)) {
            Log::channel('electronic_billing')->warning('SRI email: no company email configured', [
                'document_id' => $document->id,
            ]);

            return;
        }

        $toRecipients = $this->resolveRecipients($document);

        if (empty($toRecipients)) {
            Log::channel('electronic_billing')->warning('SRI email: no recipient email found', [
                'document_id' => $document->id,
                'document_class' => $document::class,
            ]);

            return;
        }

        SendElectronicDocumentNotificationJob::dispatch(
            modelClass: $document::class,
            modelId: (int) $document->getKey(),
            tenantRuc: $company->ruc,
            fromEmail: $company->email,
            fromName: $company->trade_name ?: $company->legal_name,
            toRecipients: $toRecipients,
            ccRecipients: [],
        );
    }

    /**
     * Resolve recipient email(s) with priority: businessPartner → customer_email → supplier_email
     *
     * @return list<string>
     */
    private function resolveRecipients(HasElectronicBilling&Model $document): array
    {
        // 1. Try businessPartner relation
        if (method_exists($document, 'businessPartner')) {
            $document->loadMissing('businessPartner:id,email');
            $emails = CustomerEmailNormalizer::normalizeAsArray($document->businessPartner?->email ?? null);
            if (! empty($emails)) {
                return $emails;
            }
        }

        // 2. Try customer_email snapshot
        $emails = CustomerEmailNormalizer::normalizeAsArray($document->customer_email ?? null);
        if (! empty($emails)) {
            return $emails;
        }

        // 3. Try supplier_email snapshot (PurchaseSettlement, Withholding)
        $emails = CustomerEmailNormalizer::normalizeAsArray($document->supplier_email ?? null);

        return $emails ?? [];
    }
}
