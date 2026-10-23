<?php

return [
    // Hide the sidebar tab and header bell until the supervisor review is done.
    'menu_visible' => env('STUDENT_DOCUMENTS_MENU', false),
    'link_ttl_days' => 14,
    'retention_days' => 7,
    'max_file_kb' => 5120,
    'max_other_files' => 3,
    'disk' => 'student_documents',
    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    'allowed_mimetypes' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ],
    'document_types' => [
        'nic' => [
            'label' => 'National Identity Card (NIC)',
            'required' => true,
        ],
        'birth_certificate' => [
            'label' => 'Birth Certificate',
            'required' => true,
        ],
        'other' => [
            'label' => 'Supporting Document',
            'required' => false,
        ],
    ],
    'authorized_roles' => [
        'DGM',
        'Program Administrator (level 01)',
        'Program Administrator (level 02)',
        'Program Administrator (level 02) Trainee',
        'Student Counselor',
        'Student Counselor Trainee',
        'Developer',
    ],
];
