<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

it('can registers the command', function () {
    expect(Artisan::all())->toHaveKey('copy:db');
});

it('can copy datable tables from source to destination', function () {
    $data = [['text' => 'text 1'], ['text' => 'text 2']];

    DB::connection('testing')->table('test')->insert($data);

    expect(DB::connection('testing')->table('test')->get())
        ->toHaveCount(2)
        ->sequence(
            fn ($item) => $item->text->toBe('text 1'),
            fn ($item) => $item->text->toBe('text 2'),
        );

    artisan('copy:db --src_connection=testing --dest_connection=sqlite')
        ->assertExitCode(0);

    expect(DB::connection('sqlite')->table('test')->get())
        ->toHaveCount(2)
        ->sequence(
            fn ($item) => $item->text->toBe('text 1'),
            fn ($item) => $item->text->toBe('text 2'),
        );
});
