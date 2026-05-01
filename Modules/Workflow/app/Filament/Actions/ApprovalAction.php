<?php

declare(strict_types=1);

namespace Modules\Workflow\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Enums\ApprovalDecision;
use Modules\Workflow\Events\ApprovalGranted;
use Modules\Workflow\Events\ApprovalRejected;
use Modules\Workflow\Events\ApprovalRequested;
use RuntimeException;

/**
 * Reusable Filament Action for approving or rejecting an approvable entity.
 *
 * Usage on a ViewRecord page:
 *
 *   ApprovalAction::makeApprove()
 *       ->flowKey('invoice_issuance')
 *       ->stepName('manager')
 *
 *   ApprovalAction::makeReject()
 *       ->flowKey('invoice_issuance')
 *       ->stepName('manager')
 */
final class ApprovalAction extends Action
{
    protected string $flowKey = '';

    protected string $stepName = '';

    protected ApprovalDecision $targetDecision = ApprovalDecision::Approved;

    protected bool $isReset = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->visible(function (?Model $record): bool {
                if (! $record instanceof Approvable) {
                    return false;
                }

                $user = Auth::user();

                if (! $user instanceof Model) {
                    return false;
                }

                if ($this->flowKey === '' || $this->stepName === '') {
                    return false;
                }

                if (! $record->canUserApprove($user, $this->flowKey, $this->stepName)) {
                    return false;
                }

                $stepDecision = $record->getApprovalFlow($this->flowKey)
                    ?->getStep($this->stepName)
                    ?->evaluate($record, $this->flowKey);

                if ($this->isReset) {
                    return $stepDecision !== null && $stepDecision !== ApprovalDecision::Open;
                }

                return $stepDecision === ApprovalDecision::Open;
            })
            ->action(function (?Model $record, array $data): void {
                if (! $record instanceof Approvable) {
                    throw new RuntimeException(__('ApprovalAction requires an Approvable model.'));
                }

                $user = Auth::user();

                if (! $user instanceof Model) {
                    return;
                }

                if ($this->flowKey === '' || $this->stepName === '') {
                    throw new RuntimeException(__('ApprovalAction requires flowKey and stepName to be set.'));
                }

                abort_unless(
                    $record->canUserApprove($user, $this->flowKey, $this->stepName),
                    403,
                    __('You are not authorized to perform this approval action.')
                );

                if ($this->isReset) {
                    $record->resetApproval($user, $this->flowKey, $this->stepName);

                    Notification::make()
                        ->title(__('Approval reset'))
                        ->success()
                        ->send();

                    return;
                }

                $approvalRecord = $record->recordApproval(
                    approver: $user,
                    flowKey: $this->flowKey,
                    stepName: $this->stepName,
                    decision: $this->targetDecision,
                    notes: $data['notes'] ?? null,
                );

                match ($this->targetDecision) {
                    ApprovalDecision::Approved => ApprovalGranted::dispatch($approvalRecord, $this->flowKey, $this->stepName),
                    ApprovalDecision::Rejected => ApprovalRejected::dispatch($approvalRecord, $this->flowKey, $this->stepName),
                    default => null,
                };

                // When a step is approved and the overall flow is still Pending,
                // notify the approvers of the next Open step.
                if ($this->targetDecision === ApprovalDecision::Approved) {
                    $flow = $record->getApprovalFlow($this->flowKey);

                    if ($flow !== null) {
                        $allRecords = $record->approvalRecords()
                            ->where('flow_key', $this->flowKey)
                            ->get()
                            ->groupBy('step');

                        foreach ($flow->getSteps() as $nextStep) {
                            if ($nextStep->evaluateWithRecords($allRecords->get($nextStep->getName(), collect())) === ApprovalDecision::Open) {
                                ApprovalRequested::dispatch($record, $this->flowKey, $nextStep->getName());
                                break;
                            }
                        }
                    }
                }

                Notification::make()
                    ->title($this->targetDecision === ApprovalDecision::Approved
                        ? __('Approval recorded')
                        : __('Approval rejected'))
                    ->when($this->targetDecision === ApprovalDecision::Approved, fn ($n) => $n->success())
                    ->when($this->targetDecision === ApprovalDecision::Rejected, fn ($n) => $n->danger())
                    ->send();
            });
    }

    public static function makeApprove(string $name = 'approve'): static
    {
        return self::make($name)
            ->targetDecision(ApprovalDecision::Approved)
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation();
    }

    public static function makeReject(string $name = 'reject'): static
    {
        return static::make($name)
            ->targetDecision(ApprovalDecision::Rejected)
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('notes')
                    ->placeholder(__('Explain the reason for the rejection...'))
                    ->maxLength(1000)
                    ->rows(3),
            ]);
    }

    public static function makeReset(string $name = 'reset_approval'): static
    {
        return static::make($name)
            ->asReset()
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->requiresConfirmation();
    }

    public function asReset(): static
    {
        $this->isReset = true;

        return $this;
    }

    public function flowKey(string $flowKey): static
    {
        $this->flowKey = $flowKey;

        return $this;
    }

    public function stepName(string $stepName): static
    {
        $this->stepName = $stepName;

        return $this;
    }

    public function targetDecision(ApprovalDecision $decision): static
    {
        $this->targetDecision = $decision;

        return $this;
    }
}
