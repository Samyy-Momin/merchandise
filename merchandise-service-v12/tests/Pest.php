<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Feature tests use DatabaseMigrations (migrate:fresh per test) to avoid
| the "cannot start a transaction within a transaction" error that occurs
| when service write methods call DB::beginTransaction() inside
| RefreshDatabase's outer wrapping transaction.
|
| Unit tests opt in to DatabaseMigrations individually when they need a DB.
*/

uses(TestCase::class, DatabaseMigrations::class)->in('Feature');
uses(TestCase::class)->in('Unit');
