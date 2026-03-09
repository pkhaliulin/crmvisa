/**
 * Единый источник логотипа VisaBor.
 * Файлы: public/images/logo.png и public/images/logo@2x.png
 * Поменял файл — поменялось везде.
 */
export function useLogo() {
    const logoUrl = '/images/logo.png';
    const logoUrl2x = '/images/logo@2x.png';
    return { logoUrl, logoUrl2x };
}
