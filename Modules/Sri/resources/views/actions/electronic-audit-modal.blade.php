@livewire(
    \Modules\Sri\Livewire\SRIAuditTabs::class,
    [
        'record' => $record,
        'wrapInSection' => false,
        'persistTabInQueryString' => false,
    ],
    key('sri-audit-modal-'.$record->getMorphClass().'-'.$record->getKey())
)