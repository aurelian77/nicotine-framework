<?php
declare(strict_types=1);

namespace nicotine;

class ContentTypes {

    public array $extensions = [

        'js' => 'application/x-javascript',
        'css' => 'text/css',

        'txt' => 'text/plain',
        'log' => 'text/plain',
        'csv' => 'text/csv',

        'json' => 'application/json',
        'xml' => 'application/xml',

        'bmp' => 'image/bmp',
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',

        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',

        'pdf' => 'application/pdf',

        'doc'   => 'application/msword',
        'xls'  => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

        'zip'   => 'application/x-zip',
        'rar' => 'application/vnd.rar',

    ];

}
