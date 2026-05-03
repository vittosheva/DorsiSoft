<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante Electrónico</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.08);">

                    {{-- Header bar --}}
                    <tr>
                        <td style="background-color:#1e293b; padding:20px 32px;">
                            <p style="margin:0; color:#ffffff; font-size:14px; font-weight:bold; letter-spacing:0.5px;">
                                @include('sales::pdf.partials.sri-logo-company', ['document' => $document->company]) &mdash; Facturación Electrónica
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">

                            <h1 style="margin:0 0 20px; font-size:22px; font-weight:bold; color:#0f172a;">
                                Has recibido un nuevo comprobante
                            </h1>

                            <p style="margin:0 0 8px; font-size:14px; color:#334155;">
                                Estimado(a) <strong>{{ $document->customer_name ?? 'Cliente' }}</strong>,
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; color:#334155;">
                                <strong>{{ $document->company?->legal_name }}</strong> ha emitido el siguiente documento:
                            </p>

                            {{-- Summary card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="border:1px solid #e2e8f0; border-radius:4px; margin-bottom:28px;">
                                <tr style="background-color:#f8fafc;">
                                    <td style="padding:10px 16px; font-size:12px; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; width:45%; border-bottom:1px solid #e2e8f0;">
                                        Tipo de documento
                                    </td>
                                    <td style="padding:10px 16px; font-size:13px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                        {{ $document->getRideSriDocumentTypeLabel() }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 16px; font-size:12px; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; width:45%; border-bottom:1px solid #e2e8f0;">
                                        Número
                                    </td>
                                    <td style="padding:10px 16px; font-size:13px; color:#0f172a; font-weight:bold; border-bottom:1px solid #e2e8f0;">
                                        {{ $document->getSriSequentialCode() ?? $document->code ?? '-' }}
                                    </td>
                                </tr>
                                <tr style="background-color:#f8fafc;">
                                    <td style="padding:10px 16px; font-size:12px; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; width:45%; border-bottom:1px solid #e2e8f0;">
                                        Fecha de emisión
                                    </td>
                                    <td style="padding:10px 16px; font-size:13px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                        {{ $document->issue_date?->format('d/m/Y') ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 16px; font-size:12px; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; width:45%;">
                                        Valor total
                                    </td>
                                    <td style="padding:10px 16px; font-size:14px; color:#0f172a; font-weight:bold;">
                                        ${{ number_format((float) ($document->total ?? 0), 2) }}
                                    </td>
                                </tr>
                            </table>

                            {{-- Orange CTA button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $viewUrl }}"
                                           style="display:inline-block; background-color:#e85d04; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold; padding:13px 32px; border-radius:4px; letter-spacing:0.3px;">
                                            Ver documento
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- XML download link --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $xmlUrl }}"
                                           style="color:#64748b; font-size:12px; text-decoration:underline;">
                                            Descargar XML
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Contact line --}}
                            <p style="margin:0; font-size:12px; color:#64748b; border-top:1px solid #e2e8f0; padding-top:16px;">
                                Si tienes alguna consulta, contacta a
                                <strong>{{ $document->company?->legal_name }}</strong>
                                @if($document->company?->email)
                                    al correo: <a href="mailto:{{ $document->company->email }}" style="color:#2563eb;">{{ $document->company->email }}</a>
                                @endif
                            </p>

                        </td>
                    </tr>

                    {{-- Brand footer --}}
                    <tr>
                        <td style="background-color:#f8fafc; padding:16px 32px; border-top:1px solid #e2e8f0; text-align:center;">
                            <p style="margin:0; font-size:10px; color:#94a3b8;">
                                UpConta es una marca registrada de <span style="color:#cc0000; font-weight:bold;">TOC Systems</span>
                                &mdash; Todos los derechos reservados &copy;{{ date('Y') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
