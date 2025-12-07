<?php

return [

    'available_modules' => [
        ['id' => 'tenants', 'route' => '/admin/tenants', 'label' => 'Tenants', 'icon' => ''],
        ['id' => 'leads', 'route' => '/admin/leads', 'label' => 'CRM', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['id' => 'forms', 'route' => '/admin/forms', 'label' => 'Form Builder', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['id' => 'webhooks', 'route' => '/admin/webhooks', 'label' => 'Webhooks', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
        ['id' => 'ai_chats', 'route' => '/admin/ai-chats', 'label' => 'AI Agents', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
        ['id' => 'api_keys', 'route' => '/admin/api-keys', 'label' => 'API Access', 'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 16.464l-1.414 1.414a1 1 0 01-1.414 0l-1.414-1.414a1 1 0 010-1.414l1.414-1.414L15 7zm3 5a1 1 0 11-2 0 1 1 0 012 0z']
    ],

    'available_modules_permissions' => [
        'view'   => ['label' => 'View',   'desc' => 'View :module'],
        'write'  => ['label' => 'Create', 'desc' => 'Create new :module'],
        'update' => ['label' => 'Update', 'desc' => 'Update :module'],
        'delete' => ['label' => 'Delete', 'desc' => 'Delete :module'],
    ],

];
