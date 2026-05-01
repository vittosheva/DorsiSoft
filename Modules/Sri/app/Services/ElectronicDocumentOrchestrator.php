<?php

declare(strict_types=1);

namespace Modules\Sri\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Company;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Contracts\SriAuthorizationServiceContract;
use Modules\Sri\Contracts\SriReceptionServiceContract;
use Modules\Sri\DTOs\SriAuthorizationResult;
use Modules\Sri\Enums\ElectronicCorrectionStatusEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Enums\SriElectronicDocumentTypeEnum;
use Modules\Sri\Enums\SriEmissionTypeEnum;
use Modules\Sri\Exceptions\ElectronicBillingException;
use Modules\Sri\Services\Xml\XmlGeneratorFactory;
use Throwable;

/**
 * Orchestrates the full electronic billing pipeline for a given SRI document.
 *
 * Pipeline:
 *   1. Generate access key (if missing)
 *   2. Generate XML
 *   3. Validate XSD (if enabled)
 *   4. Sign XML (XAdES-BES)
 *   5. Store signed XML
 *   6. Send to SRI (reception)
 *   7. Poll authorization
 */
final class ElectronicDocumentOrchestrator
{
    public function __construct(
        private readonly AccessKeyGenerator $accessKeyGenerator,
        private readonly XmlGeneratorFactory $xmlGeneratorFactory,
        private readonly XmlValidatorService $xmlValidatorService,
        private readonly XmlSigningService $xmlSigningService,
        private readonly SriReceptionServiceContract $receptionService,
        private readonly SriAuthorizationServiceContract $authorizationService,
        private readonly SriDocumentPreValidator $preValidator,
    ) {}

    /**
     * @throws ElectronicBillingException
     */
    public function process(HasElectronicBilling $document, ?int $triggeredBy = null): void
    {
        /** @var Model&HasElectronicBilling $document */
        $context = [
            'document_id' => $document->id,
            'document_type' => $document->getSriDocumentTypeCode(),
        ];

        Log::info('ElectronicDocument: process started', $context);

        if (method_exists($document, 'canProcessElectronicWorkflow') && ! $document->canProcessElectronicWorkflow()) {
            throw new ElectronicBillingException('Only issued documents that are not already locked by the SRI workflow can be processed electronically.');
        }

        ElectronicEventLogger::record(
            document: $document,
            event: 'process_started',
            statusFrom: $document->getElectronicStatus(),
            triggeredBy: $triggeredBy,
        );

        $company = Company::withoutGlobalScopes()->find($document->company_id);

        if (! $company) {
            throw new ElectronicBillingException("Company not found for document [{$document->id}].");
        }

        // 1. Eager-load relations needed for pre-validation and XML generation
        $document->loadMissing($document->getElectronicEagerLoads());

        // 2. Pre-validate document and company data before doing any work
        $this->preValidator->validate($document, $company);

        Log::info('ElectronicDocument: pre-validation passed', $context);

        // 3. Generate access key if not present
        if (blank($document->access_key)) {
            $accessKey = $this->accessKeyGenerator->generate(
                date: $this->getDocumentDate($document),
                documentTypeCode: $document->getSriDocumentTypeCode(),
                ruc: $company->ruc,
                environment: $company->sri_environment,
                establishmentCode: $document->establishment_code,
                emissionPointCode: $document->emission_point_code,
                sequentialNumber: $document->sequential_number,
                emissionType: SriEmissionTypeEnum::Normal,
            );
            $document->update(['access_key' => $accessKey]);
            Log::info('ElectronicDocument: access key generated', array_merge($context, ['access_key' => $accessKey]));
        } else {
            Log::info('ElectronicDocument: access key already set', array_merge($context, ['access_key' => $document->access_key]));
        }

        $context['access_key'] = $document->access_key;

        // 4. Generate XML
        $generator = $this->xmlGeneratorFactory->make($document->getSriDocumentTypeCode());
        $xml = $generator->generate($document);
        $document->update(['electronic_status' => ElectronicStatusEnum::XmlGenerated]);

        Log::info('ElectronicDocument: XML generated', array_merge($context, ['xml_length' => mb_strlen($xml)]));

        ElectronicEventLogger::record(
            document: $document,
            event: 'xml_generated',
            statusFrom: ElectronicStatusEnum::Pending,
            statusTo: ElectronicStatusEnum::XmlGenerated,
            triggeredBy: $triggeredBy,
        );

        // 5. Validate XSD (skip if disabled in config)
        if (config('sri.electronic.validate_xsd', true)) {
            $docTypeEnum = SriElectronicDocumentTypeEnum::from($document->getSriDocumentTypeCode());
            $this->xmlValidatorService->validate($xml, $document->getSriDocumentTypeCode(), $docTypeEnum->getXmlVersion());
            Log::info('ElectronicDocument: XSD validation passed', $context);

            ElectronicEventLogger::record(
                document: $document,
                event: 'xsd_validated',
                statusFrom: ElectronicStatusEnum::XmlGenerated,
                statusTo: ElectronicStatusEnum::XmlGenerated,
                triggeredBy: $triggeredBy,
            );
        }

        // 6. Sign XML
        $signedXml = $this->xmlSigningService->sign($xml, $company);
        $document->update(['electronic_status' => ElectronicStatusEnum::Signed]);

        Log::info('ElectronicDocument: XML signed', array_merge($context, ['signed_xml_length' => mb_strlen($signedXml)]));

        ElectronicEventLogger::record(
            document: $document,
            event: 'xml_signed',
            statusFrom: ElectronicStatusEnum::XmlGenerated,
            statusTo: ElectronicStatusEnum::Signed,
            triggeredBy: $triggeredBy,
        );

        // 7. Store signed XML
        $xmlPath = $document->getXmlStoragePath($company->ruc);
        Storage::disk(config('sri.electronic.xml_storage_disk', 'local'))->put($xmlPath, $signedXml);

        $document->update([
            'metadata' => array_merge($document->metadata ?? [], ['xml_path' => $xmlPath]),
        ]);

        Log::info('ElectronicDocument: XML stored', array_merge($context, ['xml_path' => $xmlPath]));

        ElectronicEventLogger::record(
            document: $document,
            event: 'xml_stored',
            statusFrom: ElectronicStatusEnum::Signed,
            statusTo: ElectronicStatusEnum::Signed,
            payload: ['xml_path' => $xmlPath],
            triggeredBy: $triggeredBy,
        );

        // 8. Send to SRI
        $receptionStartedAt = microtime(true);

        try {
            $receptionResult = $this->receptionService->send($signedXml, $company->sri_environment);
        } catch (Throwable $exception) {
            $this->recordTechnicalExchange(
                document: $document,
                service: 'sri_reception',
                operation: 'validarComprobante',
                status: 'failed',
                environment: $company->sri_environment->value,
                endpoint: null,
                requestSummary: [
                    'document_type' => $document->getSriDocumentTypeCode(),
                    'access_key' => $document->access_key,
                ],
                durationMs: $this->calculateDuration($receptionStartedAt),
                triggeredBy: $triggeredBy,
                exception: $exception,
            );

            throw $exception;
        }

        $this->recordTechnicalExchange(
            document: $document,
            service: 'sri_reception',
            operation: 'validarComprobante',
            status: $receptionResult->isReceived() ? 'received' : 'rejected',
            environment: $company->sri_environment->value,
            endpoint: $receptionResult->endpoint,
            requestSummary: [
                'document_type' => $document->getSriDocumentTypeCode(),
                'access_key' => $document->access_key,
            ],
            responseSummary: [
                'estado' => $receptionResult->estado,
                'mensajes' => $receptionResult->mensajes,
            ],
            requestBody: $receptionResult->requestXml,
            responseBody: $receptionResult->responseXml,
            durationMs: $this->calculateDuration($receptionStartedAt),
            triggeredBy: $triggeredBy,
        );

        if (! $receptionResult->isReceived()) {
            $correctionClassifier = app(ElectronicDocumentCorrectionClassifier::class);
            $requiresCorrection = $correctionClassifier->classifyMessages($receptionResult->mensajes) === ElectronicDocumentCorrectionClassifier::REQUIRES_CORRECTION;

            $document->update([
                'electronic_status' => ElectronicStatusEnum::Rejected,
                'correction_status' => $requiresCorrection ? ElectronicCorrectionStatusEnum::Required : ElectronicCorrectionStatusEnum::None,
                'correction_requested_at' => $requiresCorrection ? now() : null,
                'correction_reason' => $correctionClassifier->summarize($document),
                'metadata' => array_merge($document->metadata ?? [], [
                    'reception_estado' => $receptionResult->estado,
                    'reception_mensajes' => $receptionResult->mensajes,
                    'rejection_resolution' => $requiresCorrection ? ElectronicDocumentCorrectionClassifier::REQUIRES_CORRECTION : ElectronicDocumentCorrectionClassifier::RETRYABLE,
                ]),
            ]);

            Log::warning('ElectronicDocument: SRI reception rejected document', array_merge($context, [
                'estado' => $receptionResult->estado,
                'mensajes' => $receptionResult->mensajes,
            ]));

            ElectronicEventLogger::record(
                document: $document,
                event: 'rejected',
                statusFrom: ElectronicStatusEnum::Signed,
                statusTo: ElectronicStatusEnum::Rejected,
                payload: [
                    'estado' => $receptionResult->estado,
                    'mensajes' => $receptionResult->mensajes,
                    'rejection_resolution' => $requiresCorrection ? ElectronicDocumentCorrectionClassifier::REQUIRES_CORRECTION : ElectronicDocumentCorrectionClassifier::RETRYABLE,
                ],
                triggeredBy: $triggeredBy,
            );

            return;
        }

        $document->update([
            'electronic_status' => ElectronicStatusEnum::Submitted,
            'electronic_submitted_at' => now(),
            'metadata' => array_merge($document->metadata ?? [], [
                'authorization_estado' => null,
                'authorization_mensajes' => [],
                'authorization_number' => null,
                'authorization_date' => null,
                'rejection_resolution' => null,
                'error' => null,
            ]),
        ]);

        Log::info('ElectronicDocument: submitted to SRI (RECIBIDA)', $context);

        ElectronicEventLogger::record(
            document: $document,
            event: 'submitted',
            statusFrom: ElectronicStatusEnum::Signed,
            statusTo: ElectronicStatusEnum::Submitted,
            triggeredBy: $triggeredBy,
        );

        // 9. Attempt immediate authorization (SRI may respond synchronously in pruebas)
        $this->pollAuthorization($document, $company, $triggeredBy);
    }

    /**
     * Polls the SRI for authorization status. Called by PollSriAuthorization job
     * and also immediately after reception for possible same-request authorization.
     *
     * @throws ElectronicBillingException
     */
    public function pollAuthorization(HasElectronicBilling $document, ?Company $company = null, ?int $triggeredBy = null): void
    {
        /** @var Model&HasElectronicBilling $document */
        $company ??= Company::withoutGlobalScopes()->find($document->company_id);

        if (! $company || blank($document->access_key)) {
            return;
        }

        $context = [
            'document_id' => $document->id,
            'document_type' => $document->getSriDocumentTypeCode(),
            'access_key' => $document->access_key,
        ];

        Log::info('ElectronicDocument: polling SRI authorization', $context);

        $authorizationStartedAt = microtime(true);

        try {
            $result = $this->authorizationService->query($document->access_key, $company->sri_environment);
        } catch (Throwable $exception) {
            $this->recordTechnicalExchange(
                document: $document,
                service: 'sri_authorization',
                operation: 'autorizacionComprobante',
                status: 'failed',
                environment: $company->sri_environment->value,
                endpoint: null,
                requestSummary: ['access_key' => $document->access_key],
                durationMs: $this->calculateDuration($authorizationStartedAt),
                triggeredBy: $triggeredBy,
                exception: $exception,
            );

            throw $exception;
        }

        $this->recordTechnicalExchange(
            document: $document,
            service: 'sri_authorization',
            operation: 'autorizacionComprobante',
            status: $result->isAuthorized() ? 'authorized' : ($result->isRejected() ? 'rejected' : 'in_process'),
            environment: $company->sri_environment->value,
            endpoint: $result->endpoint,
            requestSummary: ['access_key' => $document->access_key],
            responseSummary: [
                'estado' => $result->estado,
                'mensajes' => $result->mensajes,
                'authorization_number' => $result->numeroAutorizacion,
                'authorization_date' => $result->fechaAutorizacion,
            ],
            requestBody: $result->requestXml,
            responseBody: $result->responseXml,
            durationMs: $this->calculateDuration($authorizationStartedAt),
            triggeredBy: $triggeredBy,
        );

        if ($result->isAuthorized()) {
            $this->handleAuthorized($document, $company, $result, $triggeredBy);
        } elseif ($result->isRejected()) {
            $correctionClassifier = app(ElectronicDocumentCorrectionClassifier::class);
            $requiresCorrection = $correctionClassifier->classifyMessages($result->mensajes) === ElectronicDocumentCorrectionClassifier::REQUIRES_CORRECTION;

            $document->update([
                'electronic_status' => ElectronicStatusEnum::Rejected,
                'correction_status' => $requiresCorrection ? ElectronicCorrectionStatusEnum::Required : ElectronicCorrectionStatusEnum::None,
                'correction_requested_at' => $requiresCorrection ? now() : null,
                'correction_reason' => $correctionClassifier->summarize($document),
                'metadata' => array_merge($document->metadata ?? [], [
                    'authorization_estado' => $result->estado,
                    'authorization_mensajes' => $result->mensajes,
                    'rejection_resolution' => $requiresCorrection ? ElectronicDocumentCorrectionClassifier::REQUIRES_CORRECTION : ElectronicDocumentCorrectionClassifier::RETRYABLE,
                ]),
            ]);

            Log::warning('ElectronicDocument: SRI authorization rejected', array_merge($context, [
                'estado' => $result->estado,
                'mensajes' => $result->mensajes,
            ]));

            ElectronicEventLogger::record(
                document: $document,
                event: 'poll_rejected',
                statusFrom: ElectronicStatusEnum::Submitted,
                statusTo: ElectronicStatusEnum::Rejected,
                payload: [
                    'estado' => $result->estado,
                    'mensajes' => $result->mensajes,
                    'rejection_resolution' => $requiresCorrection ? ElectronicDocumentCorrectionClassifier::REQUIRES_CORRECTION : ElectronicDocumentCorrectionClassifier::RETRYABLE,
                ],
                triggeredBy: $triggeredBy,
            );
        } else {
            $document->update([
                'metadata' => array_merge($document->metadata ?? [], [
                    'authorization_estado' => $result->estado,
                    'authorization_mensajes' => $result->mensajes,
                    'authorization_number' => $result->numeroAutorizacion,
                    'authorization_date' => $result->fechaAutorizacion,
                    'rejection_resolution' => null,
                ]),
            ]);

            Log::info('ElectronicDocument: SRI authorization in process (EN PROCESO)', $context);

            ElectronicEventLogger::record(
                document: $document,
                event: 'poll_in_process',
                statusFrom: ElectronicStatusEnum::Submitted,
                statusTo: ElectronicStatusEnum::Submitted,
                triggeredBy: $triggeredBy,
            );
        }
    }

    private function handleAuthorized(
        HasElectronicBilling $document,
        Company $company,
        SriAuthorizationResult $result,
        ?int $triggeredBy = null,
    ): void {
        /** @var Model&HasElectronicBilling $document */
        $metadata = array_merge($document->metadata ?? [], [
            'authorization_number' => $result->numeroAutorizacion,
            'authorization_date' => $result->fechaAutorizacion,
        ]);

        // Store RIDE (authorized XML) if provided
        if (! blank($result->comprobante)) {
            $ridePath = $document->getRideStoragePath($company->ruc);
            Storage::disk(config('sri.electronic.xml_storage_disk', 'local'))->put($ridePath, $result->comprobante);
            $metadata['ride_path'] = $ridePath;
        }

        $commercialAttributes = method_exists($document, 'syncCommercialStatusAfterAuthorization')
            ? $document->syncCommercialStatusAfterAuthorization($result->fechaAutorizacion)
            : [];

        $document->update([
            ...$commercialAttributes,
            'electronic_status' => ElectronicStatusEnum::Authorized,
            'electronic_authorized_at' => now(),
            'metadata' => $metadata,
        ]);

        Log::info('ElectronicDocument: authorized by SRI', [
            'document_id' => $document->id,
            'document_type' => $document->getSriDocumentTypeCode(),
            'access_key' => $document->access_key,
            'authorization_number' => $result->numeroAutorizacion,
            'authorization_date' => $result->fechaAutorizacion,
        ]);

        ElectronicEventLogger::record(
            document: $document,
            event: 'authorized',
            statusFrom: ElectronicStatusEnum::Submitted,
            statusTo: ElectronicStatusEnum::Authorized,
            payload: [
                'authorization_number' => $result->numeroAutorizacion,
                'authorization_date' => $result->fechaAutorizacion,
            ],
            triggeredBy: $triggeredBy,
        );
    }

    private function getDocumentDate(HasElectronicBilling $document): Carbon|CarbonImmutable
    {
        /** @var Model&HasElectronicBilling $document */
        $dateField = match (true) {
            isset($document->issue_date) && $document->issue_date !== null => $document->issue_date,
            isset($document->transport_date) && $document->transport_date !== null => $document->transport_date,
            default => null,
        };

        return $dateField instanceof Carbon || $dateField instanceof CarbonImmutable
            ? $dateField
            : now();
    }

    private function calculateDuration(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function recordTechnicalExchange(
        HasElectronicBilling $document,
        string $service,
        string $operation,
        string $status,
        ?string $environment,
        ?string $endpoint,
        array $requestSummary,
        array $responseSummary = [],
        ?string $requestBody = null,
        ?string $responseBody = null,
        ?int $durationMs = null,
        ?int $triggeredBy = null,
        ?Throwable $exception = null,
    ): void {
        SriTechnicalExchangeLogger::record(
            document: $document,
            service: $service,
            operation: $operation,
            status: $status,
            environment: $environment,
            endpoint: $endpoint,
            requestSummary: $requestSummary,
            responseSummary: $responseSummary,
            requestBody: $requestBody,
            responseBody: $responseBody,
            durationMs: $durationMs,
            triggeredBy: $triggeredBy,
            exception: $exception,
        );
    }
}
