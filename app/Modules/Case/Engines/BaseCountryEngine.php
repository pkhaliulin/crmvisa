<?php

namespace App\Modules\Case\Engines;

abstract class BaseCountryEngine implements CountryEngineInterface
{
    /**
     * Стандартные этапы обработки визовой заявки.
     */
    public function getProcessingStages(): array
    {
        return [
            ['slug' => 'document_collection', 'title' => 'Сбор документов', 'title_uz' => 'Hujjatlarni yig\'ish', 'order' => 1],
            ['slug' => 'form_filling',        'title' => 'Заполнение анкеты', 'title_uz' => 'Anketani to\'ldirish', 'order' => 2],
            ['slug' => 'review',              'title' => 'Проверка комплекта', 'title_uz' => 'Hujjatlarni tekshirish', 'order' => 3],
            ['slug' => 'appointment',         'title' => 'Запись на подачу', 'title_uz' => 'Topshirishga yozilish', 'order' => 4],
            ['slug' => 'submission',          'title' => 'Подача в посольство', 'title_uz' => 'Elchixonaga topshirish', 'order' => 5],
            ['slug' => 'processing',          'title' => 'Рассмотрение', 'title_uz' => 'Ko\'rib chiqish', 'order' => 6],
            ['slug' => 'result',              'title' => 'Результат', 'title_uz' => 'Natija', 'order' => 7],
        ];
    }

    /**
     * URL онлайн-подачи. По умолчанию нет.
     */
    public function getExternalSubmissionUrl(): ?string
    {
        return null; // TODO: integrate with real embassy API
    }

    /**
     * Информация о посольстве. По умолчанию пустая.
     */
    public function getEmbassyInfo(): array
    {
        return [
            'address'       => null,
            'phone'         => null,
            'email'         => null,
            'website'       => null,
            'working_hours' => null,
        ];
    }

    /**
     * Статус engine. По умолчанию stub.
     */
    public function getStatus(): string
    {
        return 'stub';
    }

    /**
     * Базовый набор документов, общий для большинства стран.
     */
    protected function commonDocuments(): array
    {
        return [
            [
                'name'        => 'Загранпаспорт',
                'name_uz'     => 'Xorijiy passport',
                'requirement' => 'required',
                'description' => 'Действующий загранпаспорт (минимум 2 пустые страницы, срок действия 3+ месяца после возврата)',
            ],
            [
                'name'        => 'Фото 3.5x4.5',
                'name_uz'     => 'Surat 3.5x4.5',
                'requirement' => 'required',
                'description' => 'Цветное фото на белом фоне, не старше 6 месяцев',
            ],
            [
                'name'        => 'Выписка с банковского счета',
                'name_uz'     => 'Bank hisobidan ko\'chirma',
                'requirement' => 'required',
                'description' => 'За последние 3-6 месяцев с печатью банка',
            ],
            [
                'name'        => 'Справка с места работы',
                'name_uz'     => 'Ish joyidan ma\'lumotnoma',
                'requirement' => 'required',
                'description' => 'С указанием должности, стажа и заработной платы',
            ],
            [
                'name'        => 'Медицинская страховка',
                'name_uz'     => 'Tibbiy sug\'urta',
                'requirement' => 'required',
                'description' => 'Покрытие минимум 30 000 EUR, действительна на весь срок поездки',
            ],
        ];
    }

    /**
     * Стандартные шаги формы анкеты.
     */
    protected function commonFormSteps(): array
    {
        return [
            [
                'step'     => 1,
                'title'    => 'Личные данные',
                'title_uz' => 'Shaxsiy ma\'lumotlar',
                'fields'   => [
                    ['key' => 'surname', 'label' => 'Фамилия', 'type' => 'text', 'required' => true],
                    ['key' => 'given_names', 'label' => 'Имя', 'type' => 'text', 'required' => true],
                    ['key' => 'birth_date', 'label' => 'Дата рождения', 'type' => 'date', 'required' => true],
                    ['key' => 'birth_place', 'label' => 'Место рождения', 'type' => 'text', 'required' => true],
                    ['key' => 'nationality', 'label' => 'Гражданство', 'type' => 'text', 'required' => true],
                    ['key' => 'gender', 'label' => 'Пол', 'type' => 'select', 'required' => true, 'options' => ['male', 'female']],
                    ['key' => 'marital_status', 'label' => 'Семейное положение', 'type' => 'select', 'required' => true, 'options' => ['single', 'married', 'divorced', 'widowed']],
                ],
            ],
            [
                'step'     => 2,
                'title'    => 'Паспортные данные',
                'title_uz' => 'Passport ma\'lumotlari',
                'fields'   => [
                    ['key' => 'passport_number', 'label' => 'Номер паспорта', 'type' => 'text', 'required' => true],
                    ['key' => 'passport_issue_date', 'label' => 'Дата выдачи', 'type' => 'date', 'required' => true],
                    ['key' => 'passport_expiry_date', 'label' => 'Дата окончания', 'type' => 'date', 'required' => true],
                    ['key' => 'passport_issued_by', 'label' => 'Кем выдан', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'step'     => 3,
                'title'    => 'Данные поездки',
                'title_uz' => 'Sayohat ma\'lumotlari',
                'fields'   => [
                    ['key' => 'travel_purpose', 'label' => 'Цель поездки', 'type' => 'select', 'required' => true],
                    ['key' => 'entry_date', 'label' => 'Дата въезда', 'type' => 'date', 'required' => true],
                    ['key' => 'exit_date', 'label' => 'Дата выезда', 'type' => 'date', 'required' => true],
                    ['key' => 'accommodation', 'label' => 'Место проживания', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'step'     => 4,
                'title'    => 'Финансовые данные',
                'title_uz' => 'Moliyaviy ma\'lumotlar',
                'fields'   => [
                    ['key' => 'employer', 'label' => 'Работодатель', 'type' => 'text', 'required' => true],
                    ['key' => 'position', 'label' => 'Должность', 'type' => 'text', 'required' => true],
                    ['key' => 'monthly_income', 'label' => 'Ежемесячный доход', 'type' => 'number', 'required' => false],
                    ['key' => 'trip_sponsor', 'label' => 'Спонсор поездки', 'type' => 'select', 'required' => true, 'options' => ['self', 'company', 'sponsor']],
                ],
            ],
        ];
    }
}
