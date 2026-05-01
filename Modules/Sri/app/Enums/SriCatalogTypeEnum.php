<?php

declare(strict_types=1);

namespace Modules\Sri\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SriCatalogTypeEnum: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case TipoComprobante = 'tipo_comprobante';
    case TipoIdentificacion = 'tipo_identificacion';
    case FormaPago = 'forma_pago';
    case SustentoTributario = 'sustento_tributario';
    case CodigoRetencion = 'codigo_retencion';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->getLabel()])
            ->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::TipoComprobante => __('Document Type'),
            self::TipoIdentificacion => __('Identification Type'),
            self::FormaPago => __('Payment Method'),
            self::SustentoTributario => __('Tax Basis'),
            self::CodigoRetencion => __('Withholding Code'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TipoComprobante => 'primary',
            self::TipoIdentificacion => 'info',
            self::FormaPago => 'success',
            self::SustentoTributario => 'warning',
            self::CodigoRetencion => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::TipoComprobante => Heroicon::Document,
            self::TipoIdentificacion => Heroicon::Identification,
            self::FormaPago => Heroicon::CurrencyDollar,
            self::SustentoTributario => Heroicon::ClipboardDocumentCheck,
            self::CodigoRetencion => Heroicon::Banknotes,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::TipoComprobante => __('Document Type description'),
            self::TipoIdentificacion => __('Identification Type description'),
            self::FormaPago => __('Payment Method description'),
            self::SustentoTributario => __('Tax Basis description'),
            self::CodigoRetencion => __('Withholding Code description'),
        };
    }
}
