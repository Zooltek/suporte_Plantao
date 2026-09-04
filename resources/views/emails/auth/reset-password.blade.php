<!DOCTYPE html>
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <title>Redefinição de Senha — Amura Suporte</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }
        body { margin: 0 !important; padding: 0 !important; background-color: #f1f5f9; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
        @media only screen and (max-width: 620px) {
            .email-wrapper { width: 100% !important; }
            .email-card   { border-radius: 0 !important; }
            .btn-reset    { display: block !important; width: 100% !important; }
            .padding-sm   { padding: 24px 20px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

@php
    $canEmbedImages = isset($message) && is_object($message) && method_exists($message, 'embed');
    $logoPath = public_path('img/amura-logo-light.png');
    $iconPath = public_path('img/amura-icon.png');

    $logoSrc = $canEmbedImages && is_file($logoPath)
        ? $message->embed($logoPath)
        : asset('img/amura-logo-light.png');

    $iconSrc = $canEmbedImages && is_file($iconPath)
        ? $message->embed($iconPath)
        : asset('img/amura-icon.png');
@endphp

<!-- Preheader (oculto, aparece na prévia do inbox) -->
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Você solicitou a redefinição de senha da sua conta Amura Suporte. O link expira em {{ $expiry }} minutos.
    &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
</div>

<!-- Wrapper -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f1f5f9;">
    <tr>
        <td align="center" style="padding:40px 16px;">

            <!-- Card -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="560" class="email-card email-wrapper"
                   style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                <!-- Header com gradiente -->
                <tr>
                    <td align="center"
                        style="background:linear-gradient(135deg,#f97316 0%,#f43f5e 100%);padding:36px 40px 32px;">
                        <!-- Logo -->
                        <img src="{{ $logoSrc }}"
                             alt="Amura Suporte"
                             width="140"
                             height="40"
                             style="height:auto;max-width:140px;display:block;margin:0 auto 20px;">
                        <!-- Ícone da marca -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
                            <tr>
                                <td align="center"
                                    width="60"
                                    height="60"
                                    style="width:60px;height:60px;background-color:rgba(255,255,255,0.18);border-radius:9999px;">
                                    <img src="{{ $iconSrc }}"
                                         alt=""
                                         width="28"
                                         height="28"
                                         style="display:block;margin:0 auto;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Corpo -->
                <tr>
                    <td class="padding-sm" style="padding:40px 48px 32px;">

                        <!-- Saudação -->
                        <p style="margin:0 0 6px;font-size:13px;font-weight:600;color:#f97316;letter-spacing:0.05em;text-transform:uppercase;">
                            Olá, {{ $user->name }}
                        </p>

                        <!-- Título -->
                        <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;color:#0f172a;line-height:1.3;letter-spacing:-0.02em;">
                            Redefinição de senha
                        </h1>

                        <!-- Descrição -->
                        <p style="margin:0 0 28px;font-size:15px;color:#475569;line-height:1.65;">
                            Recebemos uma solicitação para redefinir a senha da sua conta no
                            <strong style="color:#0f172a;">Amura Suporte</strong>.
                            Clique no botão abaixo para escolher uma nova senha:
                        </p>

                        <!-- Botão CTA -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 28px;">
                            <tr>
                                <td align="center"
                                    style="background:linear-gradient(135deg,#f97316 0%,#f43f5e 100%);border-radius:10px;mso-padding-alt:0;">
                                    <a href="{{ $url }}"
                                       class="btn-reset"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       style="display:inline-block;padding:14px 36px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;letter-spacing:0.01em;mso-padding-alt:14px 36px;">
                                        Redefinir minha senha
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <!-- Aviso de expiração -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                               style="background-color:#fff7ed;border:1px solid #fed7aa;border-radius:10px;margin-bottom:28px;">
                            <tr>
                                <td style="padding:14px 16px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td width="22" valign="top" style="padding-right:10px;">
                                                <span aria-hidden="true"
                                                      style="display:inline-block;width:18px;height:18px;line-height:18px;text-align:center;border-radius:9999px;background-color:#fdba74;color:#9a3412;font-size:12px;font-weight:700;">
                                                    !
                                                </span>
                                            </td>
                                            <td>
                                                <p style="margin:0;font-size:13px;color:#9a3412;line-height:1.5;">
                                                    <strong>Atenção:</strong> Este link é válido por
                                                    <strong>{{ $expiry }} minutos</strong> e pode ser usado apenas uma vez.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Link alternativo -->
                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.6;">
                            Se o botão não funcionar, copie e cole o endereço abaixo no seu navegador:
                        </p>
                        <p style="margin:0;font-size:12px;word-break:break-all;">
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               style="color:#f97316;text-decoration:underline;">{{ $url }}</a>
                        </p>

                    </td>
                </tr>

                <!-- Divisor -->
                <tr>
                    <td style="padding:0 48px;">
                        <hr style="border:none;border-top:1px solid #e2e8f0;margin:0;">
                    </td>
                </tr>

                <!-- Nota de segurança -->
                <tr>
                    <td class="padding-sm" style="padding:24px 48px 32px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                               style="background-color:#f8fafc;border-radius:8px;">
                            <tr>
                                <td style="padding:14px 16px;">
                                    <p style="margin:0;font-size:12px;color:#64748b;line-height:1.6;">
                                        🔒 <strong>Não solicitou essa redefinição?</strong>
                                        Ignore este e-mail com segurança — sua senha permanece a mesma e nenhuma alteração será feita.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center"
                        style="background-color:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;border-radius:0 0 16px 16px;">
                        <p style="margin:0 0 4px;font-size:12px;color:#94a3b8;">
                            &copy; {{ date('Y') }} &mdash; Amura Sistemas
                        </p>
                        <p style="margin:0;font-size:11px;color:#cbd5e1;">
                            Este é um e-mail automático. Não responda a esta mensagem.
                        </p>
                    </td>
                </tr>

            </table>
            <!-- /Card -->

        </td>
    </tr>
</table>
<!-- /Wrapper -->

</body>
</html>
