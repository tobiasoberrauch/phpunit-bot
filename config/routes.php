<?php

use Tob\PhpUnitBot\Command\CreateFromSourceCommand;

return [
    [
        'name'                 => 'create',
        'route'                => '<sourceFile> <testDirectory>',
        'description'          => 'It creates an unit test from a source file',
        'short_description'    => 'Create unit tests from source',
        'options_descriptions' => [
            '<sourceFile>'    => 'The source file',
            '<testDirectory>' => '',
        ],
        'defaults'             => [
            'testDirectory' => getcwd(),
        ],
        'handler'              => CreateFromSourceCommand::class,
    ],
];