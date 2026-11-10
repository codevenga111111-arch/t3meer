<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Test Setup
|--------------------------------------------------------------------------
| T3meer — اختبارات المشروع (Pest 3 + PHPUnit 11).
|
| - Feature: بيئة SQLite in-memory + RefreshDatabase (مطابقة لبيئة CI).
| - Unit: بدون قاعدة بيانات.
| المرجع: T3MEER_BACKEND_API_SPEC_v3.md — 0.4 (الاتفاقيات) و7.3 (أوامر الاختبار).
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
