<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Private disk
    |--------------------------------------------------------------------------
    |
    | User, person, and internal files never use the public disk.
    | This root is outside the web root. Optional env override for tests.
    |
    */

    'disk' => env('AUB_PRIVATE_DISK', 'aub_private'),

    'legacy_quarantine_disk' => env('AUB_LEGACY_QUARANTINE_DISK', 'aub_legacy_quarantine'),

    'thumbnail' => [
        'max_edge' => 256,
        'jpeg_quality' => 82,
    ],

    'image_jpeg_quality' => 88,

    'categories' => [

        'profile_photo' => [
            'attachable_types' => ['student', 'teacher', 'parent', 'user'],
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size_bytes' => 5 * 1024 * 1024,
            'is_image' => true,
            'sensitivity' => 'standard',
            'inline_allowed' => true,
            'thumbnail_allowed' => true,
            'audit_view' => false,
            'singleton' => true,
        ],

        'identity_document' => [
            'attachable_types' => ['student', 'parent'],
            'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => true,
        ],

        'medical_document' => [
            'attachable_types' => ['student'],
            'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => false,
        ],

        'consent_document' => [
            'attachable_types' => ['student'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => false,
        ],

        'consent_general_regulation' => [
            'attachable_types' => ['student'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => true,
        ],

        'consent_minor_entry_exit' => [
            'attachable_types' => ['student'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => true,
        ],

        'consent_rights_release' => [
            'attachable_types' => ['student'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => true,
        ],

        'certificate' => [
            'attachable_types' => ['student', 'teacher'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => false,
        ],

        'general_document' => [
            'attachable_types' => ['student', 'teacher', 'parent'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => false,
        ],

        'teacher_document' => [
            'attachable_types' => ['teacher'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => false,
        ],

        'report_card' => [
            'attachable_types' => ['student'],
            'allowed_mime_types' => ['application/pdf'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => false,
        ],

        'message_attachment' => [
            'attachable_types' => ['student', 'teacher', 'parent', 'user'],
            'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'max_size_bytes' => 10 * 1024 * 1024,
            'is_image' => false,
            'sensitivity' => 'sensitive',
            'inline_allowed' => false,
            'thumbnail_allowed' => false,
            'audit_view' => true,
            'singleton' => false,
        ],

    ],

    'legacy_map' => [
        'student' => [
            'student_photo_path' => 'profile_photo',
            'parent_id_document_path' => 'identity_document',
            'general_regulation_form_path' => 'consent_general_regulation',
            'minor_entry_exit_form_path' => 'consent_minor_entry_exit',
            'rights_release_form_path' => 'consent_rights_release',
        ],
        'teacher' => [
            'photo_path' => 'profile_photo',
        ],
    ],

];
