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
                        <td style="background:linear-gradient(135deg,#b91c1c 0%,#dc2626 100%);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:28px;font-weight:700;">VisaBor</h1>
                            <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">Уведомление безопасности</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="margin:0 0 16px;color:#0A1F44;font-size:20px;">{{ $userName }}, ваш номер телефона изменён</h2>
                            <p style="margin:0 0 16px;color:#4a5568;font-size:15px;line-height:1.6;">
                                В вашем аккаунте VisaBor произведена смена номера телефона:
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border-radius:12px;border:1px solid #fecaca;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <p style="margin:0 0 8px;font-size:14px;color:#991b1b;"><strong>Старый номер:</strong> {{ $oldPhone }}</p>
                                        <p style="margin:0;font-size:14px;color:#991b1b;"><strong>Новый номер:</strong> {{ $newPhone }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;color:#718096;font-size:13px;">
                                Если вы не меняли номер телефона — немедленно обратитесь в поддержку через Telegram: <a href="https://t.me/visabor_support" style="color:#1BA97F;">@visabor_support</a>
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
