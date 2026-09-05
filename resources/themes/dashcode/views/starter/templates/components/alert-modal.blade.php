@php
    $type = (string) ($type ?? 'success');
    $type = in_array($type, ['success', 'danger'], true) ? $type : 'success';
    $id = (string) ($id ?? 'starter-'.$type.'-modal');
    $size = (string) ($size ?? 'sm');
    $title = $title ?? ($type === 'danger' ? 'Anda yakin?' : 'Berhasil!');
    $message = $message ?? ($type === 'danger' ? 'Tindakan ini tidak dapat dibatalkan.' : 'Tindakan berhasil diselesaikan.');
    $confirmText = $confirmText ?? ($type === 'danger' ? 'Konfirmasi' : 'Selesai');
    $cancelText = $cancelText ?? 'Batal';
    $password = (bool) ($password ?? false);
    $passwordName = (string) ($passwordName ?? 'password');
    $passwordModel = $passwordModel ?? null;
    $passwordErrorKey = (string) ($passwordErrorKey ?? $passwordName);
    $passwordLabel = $passwordLabel ?? 'Password Login';
    $passwordPlaceholder = $passwordPlaceholder ?? 'Password login';
    $confirmAction = $confirmAction ?? null;
    $cancelAction = $cancelAction ?? null;
    $wireSubmit = $wireSubmit ?? null;
    $formAction = $formAction ?? null;
    $formMethod = strtoupper((string) ($formMethod ?? 'POST'));
    $dismissOnConfirm = $dismissOnConfirm ?? ($type === 'success' || (! $password && ! $confirmAction));
    $dismissOnConfirm = (bool) $dismissOnConfirm;
    $closeButton = (bool) ($closeButton ?? true);
    $visible = (bool) ($visible ?? false);
    $modalClass = trim((string) ($class ?? ''));
    $icon = $icon ?? ($type === 'danger' ? 'alert-triangle' : 'circle-check');
    $confirmClass = $type === 'danger' ? 'text-white bg-danger-500' : 'text-white bg-success-500';
    $bodyTextClass = $password ? 'text-start' : 'text-center';
    $passwordErrorMessage = isset($errors) && $errors->has($passwordErrorKey) ? $errors->first($passwordErrorKey) : null;
@endphp

<div class="modal dashcode-alert-modal dashcode-alert-modal-{{ $type }} {{ $visible ? 'show' : '' }} {{ $modalClass }}" id="{{ $id }}" tabindex="-1" role="dialog" aria-modal="{{ $visible ? 'true' : 'false' }}" aria-hidden="{{ $visible ? 'false' : 'true' }}" @if ($cancelAction) wire:click.self="{{ $cancelAction }}" @else data-starter-modal-close @endif>
    <div class="modal-dialog modal-{{ $size }} relative w-auto pointer-events-none" role="document">
        <div class="modal-content relative flex w-full flex-col bg-white text-current pointer-events-auto">
            @if ($closeButton)
                <button type="button" class="dashcode-icon-button dashcode-modal-close" @if ($cancelAction) wire:click="{{ $cancelAction }}" @else data-starter-modal-close @endif aria-label="Tutup">
                    @include('starter.templates.layouts.icon', ['name' => 'circle-x', 'class' => 'icon-sm'])
                </button>
            @endif

            @if ($password)
                <form method="POST" @if ($wireSubmit) wire:submit="{{ $wireSubmit }}" @elseif ($formAction) action="{{ $formAction }}" @endif>
                    @if ($formAction)
                        @csrf
                        @if (! in_array($formMethod, ['GET', 'POST'], true))
                            @method($formMethod)
                        @endif
                    @endif
            @endif

            <div class="modal-body {{ $bodyTextClass }} dashcode-alert-modal-body">
                <div class="text-center">
                    @include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'mb-2 text-'.$type, 'size' => 48])

                    <h3>{{ $title }}</h3>

                    <div class="text-secondary">
                        @if ($message instanceof \Illuminate\Contracts\Support\Htmlable)
                            {!! $message->toHtml() !!}
                        @else
                            {{ $message }}
                        @endif
                    </div>
                </div>

                @if ($password)
                    <div class="mt-3">
                        <label class="form-label" for="{{ $id }}-password">{{ $passwordLabel }}</label>
                        <input
                            type="password"
                            id="{{ $id }}-password"
                            name="{{ $passwordName }}"
                            class="form-control {{ $passwordErrorMessage ? 'is-invalid' : '' }}"
                            placeholder="{{ $passwordPlaceholder }}"
                            autocomplete="current-password"
                            @if ($passwordModel) wire:model.defer="{{ $passwordModel }}" @endif
                        >
                        @if ($passwordErrorMessage)
                            <div class="invalid-feedback">{{ $passwordErrorMessage }}</div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="modal-footer dashcode-modal-actions flex items-center gap-2 border-t border-slate-200">
                <button type="button" class="btn inline-flex justify-center btn-outline-dark" @if ($cancelAction) wire:click="{{ $cancelAction }}" @else data-starter-modal-close @endif>{{ $cancelText }}</button>
                <button
                    type="{{ $password ? 'submit' : 'button' }}"
                    class="btn inline-flex justify-center {{ $confirmClass }}"
                    @if (! $password && $confirmAction) wire:click="{{ $confirmAction }}" @endif
                    @if ($dismissOnConfirm) data-starter-modal-close @endif
                >{{ $confirmText }}</button>
            </div>

            @if ($password)
                </form>
            @endif
        </div>
    </div>
</div>
