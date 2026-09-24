@once
    <style>
        .starter-otp-control { position: relative; display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .625rem; width: 100%; }
        .starter-otp-control-input { position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; margin: 0; padding: 0; border: 0; opacity: 0; cursor: text; }
        .starter-otp-control-cell { position: relative; display: flex; min-width: 0; height: 3.5rem; align-items: center; justify-content: center; border: 1px solid #cbd5e1; border-radius: .75rem; background: #fff; color: #111827; cursor: text; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 1.4rem; font-weight: 700; line-height: 1; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .starter-otp-control-cell.is-filled { border-color: #94a3b8; }
        .starter-otp-control:focus-within .starter-otp-control-cell { border-color: #64748b; box-shadow: 0 0 0 3px rgba(100, 116, 139, .12); }
        .starter-otp-control:focus-within .starter-otp-control-cell.is-active::after { width: 2px; height: 1.5rem; border-radius: 999px; background: #111827; content: ''; animation: starter-otp-caret 1s steps(1, end) infinite; }
        .starter-otp-control.has-error .starter-otp-control-cell { border-color: #dc2626; }
        .starter-otp-control.has-error:focus-within .starter-otp-control-cell { box-shadow: 0 0 0 3px rgba(220, 38, 38, .1); }
        @keyframes starter-otp-caret { 0%, 49% { opacity: 1; } 50%, 100% { opacity: 0; } }
        @media (max-width: 380px) {
            .starter-otp-control { gap: .375rem; }
            .starter-otp-control-cell { height: 3.125rem; border-radius: .625rem; font-size: 1.2rem; }
        }
    </style>
@endonce

@php
    $model = $model ?? 'otpForm.code';
    $field = $field ?? $model;
    $inputId = $inputId ?? 'login-otp';
    $errorId = $errorId ?? $inputId.'-error';
    $label = $label ?? 'Kode OTP 6 digit';
@endphp

<div
    class="starter-otp-control @error($field) has-error @enderror"
    x-data="{ code: $wire.entangle(@js($model)) }"
    x-init="$nextTick(() => $refs.input.focus())"
    x-on:click="$refs.input.focus()"
    data-starter-otp-control
>
    <input
        x-ref="input"
        x-model="code"
        x-on:input="code = String(code ?? '').replace(/\D/g, '').slice(0, 6)"
        x-on:paste.prevent="code = $event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6); $refs.input.value = code"
        type="text"
        class="starter-otp-control-input"
        id="{{ $inputId }}"
        inputmode="numeric"
        pattern="[0-9]*"
        maxlength="6"
        autocomplete="one-time-code"
        aria-label="{{ $label }}"
        @error($field) aria-invalid="true" aria-describedby="{{ $errorId }}" @enderror
    >

    @foreach (range(0, 5) as $index)
        <span
            class="starter-otp-control-cell"
            x-bind:class="{
                'is-filled': String(code ?? '').length > {{ $index }},
                'is-active': String(code ?? '').length === {{ $index }}
            }"
            x-text="String(code ?? '').charAt({{ $index }})"
            aria-hidden="true"
        ></span>
    @endforeach
</div>
