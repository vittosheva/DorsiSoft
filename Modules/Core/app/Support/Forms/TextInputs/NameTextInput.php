<?php

declare(strict_types=1);

namespace Modules\Core\Support\Forms\TextInputs;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Unique;

final class NameTextInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->required()
            ->maxLength(100);
    }

    public static function getDefaultName(): ?string
    {
        return 'name';
    }

    public function tenantScopedUnique(): static
    {
        $this->unique(
            ignoreRecord: true,
            modifyRuleUsing: fn (Unique $rule): Unique => $rule
                ->where('company_id', Filament::getTenant()?->getKey())
                ->whereNull('deleted_at'),
        );

        return $this;
    }
}
