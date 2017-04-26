<?php

use Tgallice\FBMessenger\Model\Button\Postback;

return [
    'get_started' => 'GET_STARTED',
    'greeting_text' => [
        'default' => 'لاهل بك {{user_first_name}} فى خارطة التحرش!',
        'localized' => [
            [
                'locale' => 'en_US',
                'text' => 'Welcome {{user_first_name}} to HarassMap!'
            ],
            [
                'locale' => 'ar_AR',
                'text' => 'لاهل بك {{user_first_name}} فى خارطة التحرش!'
            ],
        ]
    ],
    'persistent_menu' => [
        [
            'locale' => 'default',
            'composer_input_disabled' => false,
            'call_to_actions' => [
                new Postback('تغيير اللغة إلى English', 'CHANGE_LANGUAGE'),
                new Postback('المساعدة', 'GET_STARTED'),
            ]
        ],
        [
            'locale' => 'en_US',
            'composer_input_disabled' => false,
            'call_to_actions' => [
                new Postback('Change language to العربية', 'CHANGE_LANGUAGE'),
                new Postback('Help', 'GET_STARTED'),
            ]
        ],
        [
            'locale' => 'ar_AR',
            'composer_input_disabled' => false,
            'call_to_actions' => [
                new Postback('تغيير اللغة إلى English', 'CHANGE_LANGUAGE'),
                new Postback('المساعدة', 'GET_STARTED'),
            ]
        ],
    ]
];
