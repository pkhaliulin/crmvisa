<?php

return [
    'draft' => [
        'label'   => 'Черновик',
        'order'   => 0,
        'tooltip' => 'Заявка создана, агентство не выбрано',
        'color'   => 'gray',
    ],
    'submitted' => [
        'label'   => 'Отправлена',
        'order'   => 1,
        'tooltip' => 'Заявка отправлена в агентство, ожидает принятия',
        'color'   => 'blue',
    ],
    'manager_assigned' => [
        'label'   => 'Менеджер назначен',
        'order'   => 2,
        'tooltip' => 'Персональный менеджер назначен и свяжется с вами',
        'color'   => 'indigo',
    ],
    'document_collection' => [
        'label'   => 'Сбор документов',
        'order'   => 3,
        'tooltip' => 'Идёт сбор необходимых документов',
        'color'   => 'yellow',
    ],
    'submitted_to_embassy' => [
        'label'   => 'Подана в посольство',
        'order'   => 4,
        'tooltip' => 'Документы переданы в посольство на рассмотрение',
        'color'   => 'orange',
    ],
    'decision_pending' => [
        'label'   => 'На рассмотрении',
        'order'   => 5,
        'tooltip' => 'Посольство рассматривает заявку',
        'color'   => 'purple',
    ],
    'completed' => [
        'label'   => 'Одобрено',
        'order'   => 6,
        'tooltip' => 'Виза одобрена',
        'color'   => 'green',
    ],
    'rejected' => [
        'label'   => 'Отказ',
        'order'   => 7,
        'tooltip' => 'В визе отказано',
        'color'   => 'red',
    ],
];
