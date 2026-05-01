<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

final class CreateRelatedDocumentAction extends Action
{
    /**
     * @var class-string
     */
    private string $resourceClass;

    private string $resourceParameter = 'business_partner_id';

    private string $stateField = 'id';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Create'))
            ->icon(Heroicon::Plus)
            ->openUrlInNewTab()
            ->url(function (Get $get): string {
                return $this->resourceClass::getUrl('create', [
                    $this->resourceParameter => $get($this->stateField),
                ]);
            });
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function resourceClass(string $resourceClass): static
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    public function resourceParameter(string $resourceParameter): static
    {
        $this->resourceParameter = $resourceParameter;

        return $this;
    }

    public function stateField(string $stateField): static
    {
        $this->stateField = $stateField;

        return $this;
    }
}
