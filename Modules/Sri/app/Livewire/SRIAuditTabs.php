<?php

declare(strict_types=1);

namespace Modules\Sri\Livewire;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View as ViewContract;
use Livewire\Component;
use Modules\Sri\Support\Concerns\InteractsWithElectronicAuditData;
use Modules\Sri\Support\ElectronicAudit\ElectronicAuditViewModelFactory;

final class SRIAuditTabs extends Component implements HasSchemas
{
    use InteractsWithElectronicAuditData;
    use InteractsWithSchemas;

    public bool $wrapInSection = true;

    public bool $persistTabInQueryString = true;

    public function content(Schema $schema): Schema
    {
        if (! $this->shouldShowWidget()) {
            return $schema->components([]);
        }

        $tabs = Tabs::make('sri_audit_tabs')
            ->tabs($this->getTabs());

        if ($this->persistTabInQueryString) {
            $tabs->persistTabInQueryString('sri-audit-tab');
        }

        if (! $this->wrapInSection) {
            return $schema->components([$tabs]);
        }

        return $schema
            ->components([
                Section::make(__('SRI Audit Panel'))
                    ->schema([
                        $tabs,
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function render(): ViewContract
    {
        return view('sri::livewire.sri-audit-tabs');
    }

    public function shouldShowWidget(): bool
    {
        return $this->hasElectronicAuditRecord();
    }

    /**
     * @return array<int, Tab>
     */
    private function getTabs(): array
    {
        $viewModel = app(ElectronicAuditViewModelFactory::class)->make($this->getAuditData());

        return [
            Tab::make(__('Summary'))
                ->schema([
                    View::make('sri::livewire.sri-audit-tabs.summary')
                        ->viewData([
                            'summaryItems' => $viewModel['summary_items'],
                            'latestError' => $viewModel['latest_error'],
                        ]),
                ]),
            Tab::make(__('SRI History'))
                ->badge($viewModel['events_count'] > 0 ? (string) $viewModel['events_count'] : null)
                ->schema([
                    View::make('sri::livewire.sri-audit-tabs.timeline')
                        ->viewData([
                            'events' => $viewModel['events'],
                        ]),
                ]),
            Tab::make(__('Technical Interactions'))
                ->badge($viewModel['exchanges_count'] > 0 ? (string) $viewModel['exchanges_count'] : null)
                ->schema([
                    View::make('sri::livewire.sri-audit-tabs.technical')
                        ->viewData([
                            'exchanges' => $viewModel['exchanges'],
                        ]),
                ]),
            Tab::make(__('XML and Files'))
                ->schema([
                    View::make('sri::livewire.sri-audit-tabs.xml')
                        ->viewData([
                            'xmlPath' => $viewModel['xml_path'],
                            'ridePath' => $viewModel['ride_path'],
                            'xmlPreview' => $viewModel['xml_preview'],
                        ]),
                ]),
        ];
    }
}
