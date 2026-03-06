<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0A1F44 0%,#1a3a6e 100%);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:28px;font-weight:700;">VisaBor</h1>
                            <p style="margin:8px 0 0;color:rgba(255,255,255,0.7);font-size:14px;">Подтверждение email</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="margin:0 0 16px;color:#0A1F44;font-size:20px;">Здравствуйте, {{ $userName }}!</h2>
                            <p style="margin:0 0 24px;color:#4a5568;font-size:15px;line-height:1.6;">
                                Для подтверждения вашего email-адреса нажмите кнопку ниже:
                            </p>
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="background:#1BA97F;border-radius:12px;">
                                        <a href="{{ $verifyUrl }}" style="display:inline-block;padding:14px 40px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;">
                                            Подтвердить email
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;color:#718096;font-size:13px;text-align:center;">
                                Или скопируйте ссылку в браузер:
                            </p>
                            <p style="margin:0;color:#4a5568;font-size:12px;word-break:break-all;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
                                {{ $verifyUrl }}
                            </p>
                            <p style="margin:24px 0 0;color:#a0aec0;font-size:12px;text-align:center;">
                                Ссылка действительна 24 часа. Если вы не регистрировались — проигнорируйте это письмо.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:24px 40px;border-top:1px solid #e2e8f0;text-align:center;">
                            <p style="margin:0;color:#a0aec0;font-size:12px;">Это автоматическое письмо от VisaBor. Не отвечайте на него.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
