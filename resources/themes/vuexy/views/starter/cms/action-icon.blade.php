@php
    $name = match ($action) {
        'add' => 'plus', 'save' => 'device-floppy', 'upload' => 'upload',
        'archive' => 'archive', 'restore' => 'restore', 'delete' => 'trash',
        'check' => 'check', 'draft' => 'pencil', 'guide' => 'book',
    };
@endphp
@include('starter.templates.layouts.icon', ['name' => $name, 'class' => 'cms-action-icon'])
