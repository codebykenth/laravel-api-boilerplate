<?php

arch('app does not leave debugging functions in production code')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('models extend eloquent base model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('all controllers extend base controller')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller');

arch('all DTOs implement DataObject interface')
    ->expect('App\DTOs')
    ->toImplement('App\DTOs\Contracts\DataObject')
    ->ignoring('App\DTOs\Contracts');

arch('repositories implement their contracts')
    ->expect('App\Repositories\UserRepository')
    ->toImplement('App\Repositories\UserRepositoryInterface');
