<?php

use Tgallice\FBMessenger\Model\Button\Postback;

return [
    'get_started' => 'get_started',
    'greeting_text' => [
        'default' => 'لاهل بك فى خارطة التحرش!',
        'localized' => [
            [
                'locale' => 'en_US',
                'text' => 'Welcome to HarassMap!'
            ],
            [
                'locale' => 'ar_AR',
                'text' => 'لاهل بك فى خارطة التحرش!'
            ],
        ]
    ],
    'persistent_menu' => [
        [
            'locale' => 'default',
            'composer_input_disabled' => false,
            'call_to_actions' => [
                new Postback('تغيير اللغة إلى English', 'CHANGE_LANGUAGE'),
                new Postback('الإبلاغ عن حالة تحرش', 'REPORT_INCIDENT'),
                new Postback('الاستعلام عن بلاغات التحرش', 'GET_INCIDENTS'),
            ]
        ],
        [
            'locale' => 'en_US',
            'composer_input_disabled' => false,
            'call_to_actions' => [
                new Postback('Change language to العربية', 'CHANGE_LANGUAGE'),
                new Postback('Report Harassment Incident', 'REPORT_INCIDENT'),
                new Postback('Get Harassment Incidents', 'GET_INCIDENTS'),
            ]
        ],
        [
            'locale' => 'ar_AR',
            'composer_input_disabled' => false,
            'call_to_actions' => [
                new Postback('تغيير اللغة إلى English', 'CHANGE_LANGUAGE'),
                new Postback('الإبلاغ عن حالة تحرش', 'REPORT_INCIDENT'),
                new Postback('الاستعلام عن بلاغات التحرش', 'GET_INCIDENTS'),
            ]
        ],
    ]
];
