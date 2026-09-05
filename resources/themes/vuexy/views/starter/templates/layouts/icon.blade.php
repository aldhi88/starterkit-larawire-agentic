@php($iconName = preg_replace('/[^a-z0-9-]/', '', $name ?? 'circle'))
<i class="icon-base ti tabler-{{ $iconName }} {{ $class ?? 'icon-md' }}" aria-hidden="true"></i>
