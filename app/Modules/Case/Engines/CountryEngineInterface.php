<?php

namespace App\Modules\Case\Engines;

interface CountryEngineInterface
{
    /**
     * ISO 3166-1 alpha-2 код страны.
     */
    public function getCountryCode(): string;

    /**
     * Название страны (для отображения).
     */
    public function getCountryName(): string;

    /**
     * Доступные типы виз для данной страны.
     *
     * @return array<int, array{code: string, label: string, label_uz: string, schengen: bool}>
     */
    public function getVisaTypes(): array;

    /**
     * Список требуемых документов для указанного типа визы.
     *
     * @return array<int, array{name: string, name_uz: string, requirement: string, description: ?string}>
     */
    public function getRequiredDocuments(string $visaType): array;

    /**
     * Шаги формы анкеты для указанного типа визы.
     *
     * @return array<int, array{step: int, title: string, title_uz: string, fields: array}>
     */
    public function getFormSteps(string $visaType): array;

    /**
     * Этапы обработки заявки (pipeline).
     *
     * @return array<int, array{slug: string, title: string, title_uz: string, order: int}>
     */
    public function getProcessingStages(): array;

    /**
     * Ожидаемое время обработки в днях.
     */
    public function getEstimatedDays(string $visaType): int;

    /**
     * URL для онлайн-подачи на портале посольства (null если нет).
     */
    public function getExternalSubmissionUrl(): ?string;

    /**
     * Информация о посольстве/консульстве в Ташкенте.
     *
     * @return array{address: ?string, phone: ?string, email: ?string, website: ?string, working_hours: ?string}
     */
    public function getEmbassyInfo(): array;

    /**
     * Является ли страна частью Шенгенской зоны.
     */
    public function isSchengen(): bool;

    /**
     * Статус готовности engine: pilot, stub, planned.
     */
    public function getStatus(): string;
}
