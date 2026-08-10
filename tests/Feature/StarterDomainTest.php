<?php

use Altekno\StarterKit\Support\Starter\StarterDomain;

it('derives root session settings from the current app URL', function (): void {
    expect(StarterDomain::host('https://ERP.Example.com/path'))->toBe('erp.example.com')
        ->and(StarterDomain::sessionDomain('https://erp.example.com'))->toBe('.erp.example.com')
        ->and(StarterDomain::secureCookie('https://erp.example.com'))->toBeTrue()
        ->and(StarterDomain::sessionDomain('http://localhost'))->toBeNull()
        ->and(StarterDomain::secureCookie('http://localhost'))->toBeFalse();
});
