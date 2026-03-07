<?php

return [
    'draft' => [
        'label'   => 'Черновик',
        'order'   => 0,
        'tooltip' => 'Заявка создана, агентство не выбрано',
        'color'   => 'gray',
    ],
    'awaiting_payment' => [
        'label'   => 'Ожидание оплаты',
        'order'   => 1,
        'tooltip' => 'Агентство выбрано. Оплатите услугу, чтобы заявка была передана агентству.',
        'color'   => 'amber',
    ],
    'submitted' => [
        'label'   => 'Отправлена',
        'order'   => 2,
        'tooltip' => 'Заявка оплачена и отправлена в агентство, ожидает принятия',
        'color'   => 'blue',
    ],
    'manager_assigned' => [
        'label'   => 'Менеджер назначен',
        'order'   => 3,
        'tooltip' => 'Персональный менеджер назначен и свяжется с вами',
        'color'   => 'indigo',
    ],
    'document_collection' => [
        'label'   => 'Сбор документов',
        'order'   => 4,
        'tooltip' => 'Идёт сбор необходимых документов',
        'color'   => 'yellow',
    ],
    'document_review' => [
        'label'   => 'Проверка документов',
        'order'   => 5,
        'tooltip' => 'Менеджер проверяет загруженные документы',
        'color'   => 'amber',
    ],
    'translation' => [
        'label'   => 'Перевод',
        'order'   => 6,
        'tooltip' => 'Документы на переводе и нотариальном заверении',
        'color'   => 'cyan',
    ],
    'ready_for_submission' => [
        'label'   => 'Готов к подаче',
        'order'   => 7,
        'tooltip' => 'Пакет документов полный, ожидается подача в посольство',
        'color'   => 'orange',
    ],
    'under_review' => [
        'label'   => 'Рассмотрение',
        'order'   => 8,
        'tooltip' => 'Посольство рассматривает заявку',
        'color'   => 'purple',
    ],
    'completed' => [
        'label'   => 'Одобрено',
        'order'   => 9,
        'tooltip' => 'Виза одобрена',
        'color'   => 'green',
    ],
    'rejected' => [
        'label'   => 'Отказ',
        'order'   => 10,
        'tooltip' => 'В визе отказано',
        'color'   => 'red',
    ],
    'cancelled' => [
        'label'   => 'Отменена',
        'order'   => 11,
        'tooltip' => 'Заявка отменена клиентом',
        'color'   => 'gray',
    ],
];
