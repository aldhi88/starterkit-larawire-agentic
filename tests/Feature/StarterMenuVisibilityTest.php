<?php

use Aldhi88\StarterKit\Contracts\Starter\AppInterface;
use Aldhi88\StarterKit\Contracts\Starter\AppModInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientRoleInterface;
use Aldhi88\StarterKit\Models\Starter\AppMenu;
use Aldhi88\StarterKit\Models\Starter\AppMod;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Services\Starter\StarterContextService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

it('excludes hidden menu subtrees and modules without visible root menus', function (): void {
    $visibleChild = new AppMenu([
        'label' => 'Visible Child',
        'is_visible' => true,
        'app_route_id' => 1,
    ]);
    $visibleChild->setRelation('childrenRecursive', new EloquentCollection);
    $visibleChild->setRelation('route', null);

    $hiddenChild = new AppMenu([
        'label' => 'Hidden Child',
        'is_visible' => false,
    ]);
    $hiddenChild->setRelation('childrenRecursive', new EloquentCollection);

    $visibleParent = new AppMenu([
        'label' => 'Visible Parent',
        'is_visible' => true,
    ]);
    $visibleParent->setRelation('childrenRecursive', new EloquentCollection([
        $visibleChild,
        $hiddenChild,
    ]));

    $hiddenParent = new AppMenu([
        'label' => 'Hidden Parent',
        'is_visible' => false,
    ]);
    $hiddenParent->setRelation('childrenRecursive', new EloquentCollection([
        new AppMenu([
            'label' => 'Suppressed Descendant',
            'is_visible' => true,
        ]),
    ]));

    $emptyGroup = new AppMenu([
        'label' => 'Empty Group',
        'is_visible' => true,
    ]);
    $emptyGroup->setRelation('childrenRecursive', new EloquentCollection([$hiddenChild]));

    $visibleModule = new AppMod(['name' => 'Visible Module']);
    $visibleModule->setRelation('menus', new EloquentCollection([
        $visibleParent,
        $hiddenParent,
        $emptyGroup,
    ]));

    $hiddenModule = new AppMod(['name' => 'Hidden Module']);
    $hiddenModule->setRelation('menus', new EloquentCollection([$hiddenParent]));

    $service = new StarterContextService(
        Mockery::mock(AppInterface::class),
        Mockery::mock(AppModInterface::class),
        Mockery::mock(ClientRoleInterface::class),
        Mockery::mock(ClientInterface::class),
        Mockery::mock(ClientLoginInterface::class),
        Mockery::mock(StarterConfigService::class),
    );

    $method = new ReflectionMethod($service, 'sidebarPayload');
    $payload = $method->invoke($service, new EloquentCollection([
        $visibleModule,
        $hiddenModule,
    ]));

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['name'])->toBe('Visible Module')
        ->and($payload[0]['menuLabels'])->toBe('Visible Parent')
        ->and($payload[0]['menus'])->toHaveCount(1)
        ->and($payload[0]['menus'][0]['label'])->toBe('Visible Parent')
        ->and($payload[0]['menus'][0]['hasChildren'])->toBeTrue()
        ->and($payload[0]['menus'][0]['children'])->toHaveCount(1)
        ->and($payload[0]['menus'][0]['children'][0]['label'])->toBe('Visible Child');
});
