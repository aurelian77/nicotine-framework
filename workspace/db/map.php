<?php
declare(strict_types=1);

namespace workspace\db;

use nicotine\Registry;

Registry::set('map', [
/*
    'authors' => [
        // many 'authors' to many 'books'
        'books' => [
            'pivot' => 'authors_books',
            'link' => 'author_id',
            'parentLink' => 'book_id'
        ],

        // one 'author' to one 'profile'
        'profiles' => [
            'link' => 'author_id',
        ],

        // one 'author' to one 'address'
        'addresses' => [
            'link' => 'author_id',
        ],
    ],

    'addresses' => [
        // many 'addresses' to one 'street'
        'streets' => [
            'link' => 'address_id',
        ],
    ],

    'streets' => [
        // one 'street' to many 'houses'
        'houses' => [
            'link' => 'street_id'
        ],
    ],
*/
]);
