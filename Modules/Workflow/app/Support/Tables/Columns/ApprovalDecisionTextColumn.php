<?php

declare(strict_types=1);

namespace Modules\Workflow\Support\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Modules\Workflow\Enums\ApprovalDecision;

final class ApprovalDecisionTextColumn extends TextColumn
{
    private ?string $flowKey = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Approval'))
            ->badge()
            ->toggleable(isToggledHiddenByDefault: true)
            ->getStateUsing(function ($record): ?ApprovalDecision {
                if (blank($this->flowKey) || ! method_exists($record, 'approvalDecision')) {
                    return null;
                }

                $decision = $record->approvalDecision($this->flowKey);

                return $decision !== ApprovalDecision::Open ? $decision : null;
            })
            ->formatStateUsing(fn (?ApprovalDecision $state): string => $state?->getLabel() ?? '')
            ->color(fn (?ApprovalDecision $state): ?string => $state?->getColor());
    }

    public static function forFlow(string $name, string $flowKey): static
    {
        return self::make($name)->flow($flowKey);
    }

    public function flow(string $flowKey): static
    {
        $this->flowKey = $flowKey;

        return $this;
    }
}
