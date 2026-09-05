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
    $statusClass = $type === 'danger' ? 'bg-danger' : 'bg-success';
    $confirmClass = $type === 'danger' ? 'btn-danger' : 'btn-success';
    $bodyTextClass = $password ? 'text-start' : 'text-center';
    $passwordErrorMessage = isset($errors) && $errors->has($passwordErrorKey) ? $errors->first($passwordErrorKey) : null;
@endphp

<div class="modal modal-blur fade {{ $visible ? 'show d-block' : '' }} {{ $modalClass }}" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="{{ $visible ? 'false' : 'true' }}" @if ($cancelAction) wire:click.self="{{ $cancelAction }}" @else data-bs-dismiss="modal" @endif>
    <div class="modal-dialog modal-{{ $size }}" role="document">
        <div class="modal-content">
            @if ($closeButton)
                <button type="button" class="btn-close" @if ($cancelAction) wire:click="{{ $cancelAction }}" @else data-bs-dismiss="modal" @endif aria-label="Tutup"></button>
            @endif

            <div class="modal-status {{ $statusClass }}"></div>

            @if ($password)
                <form method="POST" @if ($wireSubmit) wire:submit="{{ $wireSubmit }}" @elseif ($formAction) action="{{ $formAction }}" @endif>
                    @if ($formAction)
                        @csrf
                        @if (! in_array($formMethod, ['GET', 'POST'], true))
                            @method($formMethod)
                        @endif
                    @endif
            @endif

            <div class="modal-body {{ $bodyTextClass }} py-4">
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

            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" @if ($cancelAction) wire:click="{{ $cancelAction }}" @else data-bs-dismiss="modal" @endif>{{ $cancelText }}</button>
                        </div>
                        <div class="col">
                            <button
                                type="{{ $password ? 'submit' : 'button' }}"
                                class="btn {{ $confirmClass }} w-100"
                                @if (! $password && $confirmAction) wire:click="{{ $confirmAction }}" @endif
                                @if ($dismissOnConfirm) data-bs-dismiss="modal" @endif
                            >{{ $confirmText }}</button>
                        </div>
                    </div>
                </div>
            </div>

            @if ($password)
                </form>
            @endif
        </div>
    </div>
</div>
