<?php

declare(strict_types=1);

namespace Modules\Core\Support\Concerns;

use Illuminate\Contracts\View\View as ViewContract;

trait HasBeforeHeadingActions
{
    public function getHeader(): ?ViewContract
    {
        $beforeActions = $this->getBeforeHeadingActions();

        if (empty($beforeActions)) {
            return parent::getHeader();
        }

        return view('core::components.page-header-with-before-actions', [
            'heading' => $this->getHeading(),
            'subheading' => $this->getSubheading(),
            'actions' => $this->getCachedHeaderActions(),
            'beforeActions' => $beforeActions,
            'actionsAlignment' => $this->getHeaderActionsAlignment(),
            'breadcrumbs' => $this->getBreadcrumbs(),
        ]);
    }

    protected function getBeforeHeadingActions(): array
    {
        return [];
    }
}
