@once
    <style>
        .starter-toast-stack {
            display: flex;
            flex-direction: column;
            gap: .8rem;
            pointer-events: none;
            position: fixed;
            right: 1rem;
            top: 1rem;
            width: min(25rem, calc(100vw - 2rem));
            z-index: 2060;
        }

        .starter-toast {
            align-items: flex-start;
            background: var(--starter-toast-bg);
            border: 0;
            border-left: .25rem solid var(--starter-toast-accent);
            border-radius: .125rem;
            box-shadow: 0 .45rem 1rem rgba(24, 36, 51, .15);
            color: var(--tblr-body-color);
            display: flex;
            gap: 1rem;
            min-height: 4.75rem;
            opacity: 0;
            overflow: hidden;
            padding: 1rem 1rem .95rem 1.2rem;
            pointer-events: auto;
            position: relative;
            transform: translateY(-.5rem);
            transition: opacity .18s ease, transform .18s ease;
        }

        .starter-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .starter-toast.is-leaving {
            opacity: 0;
            transform: translateY(-.35rem);
        }

        .starter-toast-success {
            --starter-toast-accent: #138a22;
            --starter-toast-bg: #eef9f0;
        }

        .starter-toast-info {
            --starter-toast-accent: #3468e7;
            --starter-toast-bg: #edf2ff;
        }

        .starter-toast-warning {
            --starter-toast-accent: #f2b600;
            --starter-toast-bg: #fffbe8;
        }

        .starter-toast-danger {
            --starter-toast-accent: #ef1f1f;
            --starter-toast-bg: #fff0f1;
        }

        .starter-toast-icon {
            align-self: center;
            align-items: center;
            background: var(--starter-toast-accent);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            flex: 0 0 1.55rem;
            height: 1.55rem;
            justify-content: center;
            width: 1.55rem;
        }

        .starter-toast-icon svg {
            height: 1.05rem;
            width: 1.05rem;
        }

        .starter-toast-body {
            flex: 1;
            min-width: 0;
        }

        .starter-toast-title {
            color: #202428;
            font-size: .95rem;
            font-weight: 700;
            line-height: 1.35;
            margin-bottom: .2rem;
        }

        .starter-toast-message {
            color: #202428;
            font-size: .92rem;
            line-height: 1.35;
        }

        .starter-toast-close {
            flex: 0 0 auto;
            filter: none;
            height: 1rem;
            margin-left: .35rem;
            margin-top: -.05rem;
            opacity: 1;
            padding: 0;
            width: 1rem;
        }

        .starter-toast-close:hover,
        .starter-toast-close:focus {
            opacity: 1;
        }

        .starter-toast-progress {
            animation: starter-toast-progress var(--starter-toast-duration, 4500ms) linear forwards;
            background: var(--starter-toast-accent);
            bottom: 0;
            display: none;
            height: .125rem;
            left: 0;
            opacity: .2;
            position: absolute;
            transform-origin: left;
            width: 100%;
        }

        @keyframes starter-toast-progress {
            to {
                transform: scaleX(0);
            }
        }

        @media (max-width: 575.98px) {
            .starter-toast-stack {
                left: .75rem;
                right: .75rem;
                top: .75rem;
                width: auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .starter-toast {
                transition: none;
            }

            .starter-toast-progress {
                animation: none;
            }
        }
    </style>
@endonce

@php
    $flashToast = session('starter-toast');
@endphp

<div class="starter-toast-stack" data-starter-toast-stack aria-live="polite" aria-atomic="false"></div>

@if (is_array($flashToast) && filled($flashToast['message'] ?? null))
    <div
        hidden
        data-starter-flash-toast
        data-type="{{ $flashToast['type'] ?? 'info' }}"
        data-message="{{ $flashToast['message'] }}"
        @if (filled($flashToast['title'] ?? null)) data-title="{{ $flashToast['title'] }}" @endif
        @if (filled($flashToast['duration'] ?? null)) data-duration="{{ $flashToast['duration'] }}" @endif
    ></div>
@endif
