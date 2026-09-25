window.StarterTemplate = Object.assign(window.StarterTemplate || {}, {
    normalizeUrl(url) {
        const parsed = new URL(url, window.location.href);
        parsed.hash = '';
        parsed.search = '';
        parsed.pathname = parsed.pathname.replace(/\/+$/, '') || '/';

        return parsed.href;
    },
    isSameUrl(url, compareUrl = window.location.href) {
        return this.normalizeUrl(url) === this.normalizeUrl(compareUrl);
    },
    normalizeNavigationUrl(url) {
        const parsed = new URL(url, window.location.href);
        parsed.hash = '';
        parsed.pathname = parsed.pathname.replace(/\/+$/, '') || '/';

        return parsed.href;
    },
    isSameNavigationUrl(url, compareUrl = window.location.href) {
        return this.normalizeNavigationUrl(url) === this.normalizeNavigationUrl(compareUrl);
    },
    themeAdapter() {
        return window.StarterThemeAdapter || {};
    },
    showNavigateLoader() {
        this.navigating = true;
        clearTimeout(this.navigateLoaderTimer);
        this.positionNavigateLoader();
        document.querySelector('.starter-navigate-loader')?.setAttribute('aria-hidden', 'false');
        document.body?.classList.add('starter-is-navigating');
    },
    hideNavigateLoader() {
        this.navigating = false;
        clearTimeout(this.navigateLoaderTimer);
        this.navigateLoaderTimer = setTimeout(() => {
            if (! this.navigating) {
                document.querySelector('.starter-navigate-loader')?.setAttribute('aria-hidden', 'true');
                document.body?.classList.remove('starter-is-navigating');
            }
        }, 140);
    },
    showLivewireLoader(components = []) {
        this.livewireLoadingCount = (this.livewireLoadingCount || 0) + 1;
        this.livewireLoadingComponents = this.livewireLoadingComponents || new Set();
        clearTimeout(this.livewireLoaderTimer);

        components.filter(Boolean).forEach((component) => {
            this.livewireLoadingComponents.add(component);
            component.el?.setAttribute('data-starter-livewire-loading', '');
            component.el?.setAttribute('aria-busy', 'true');
        });

        this.positionNavigateLoader();
        document.querySelector('[data-starter-livewire-loader]')?.setAttribute('aria-hidden', 'false');
        document.body?.classList.add('starter-livewire-is-loading');
    },
    isPassiveLivewireMessage(message) {
        const root = message?.component?.el;
        const calls = Array.isArray(message?.calls) ? message.calls : [];

        if (calls.length === 0) {
            return false;
        }

        if (calls.every((call) => call?.metadata?.type === 'poll')) {
            return true;
        }

        return root?.hasAttribute?.('data-starter-passive')
            || root?.hasAttribute?.('data-starter-silent-poll');
    },
    isSilentLivewireMessage(message) {
        return this.isPassiveLivewireMessage(message);
    },
    hideLivewireLoader() {
        this.livewireLoadingCount = Math.max((this.livewireLoadingCount || 1) - 1, 0);

        if (this.livewireLoadingCount > 0) {
            return;
        }

        clearTimeout(this.livewireLoaderTimer);
        this.clearLivewireLoader();
    },
    clearLivewireLoader() {
        this.livewireLoadingCount = 0;

        (this.livewireLoadingComponents || new Set()).forEach((component) => {
            component.el?.removeAttribute('data-starter-livewire-loading');
            component.el?.removeAttribute('aria-busy');
        });

        document.querySelectorAll('[data-starter-livewire-loading]').forEach((element) => {
            element.removeAttribute('data-starter-livewire-loading');
            element.removeAttribute('aria-busy');
        });

        this.livewireLoadingComponents?.clear();
        document.querySelector('[data-starter-livewire-loader]')?.setAttribute('aria-hidden', 'true');
        document.body?.classList.remove('starter-livewire-is-loading');
    },
    authLoginUrl(redirect = window.location.href) {
        const configured = document.querySelector('meta[name="starter-auth-login-url"]')?.content;
        const fallback = `${window.location.protocol}//auth.${window.location.hostname.replace(/^auth\./, '')}/login`;
        const url = new URL(configured || fallback, window.location.href);

        if (redirect) {
            url.searchParams.set('redirect', redirect);
        }

        return url.href;
    },
    redirectToLogin(redirect = null) {
        this.beginTopLevelNavigation(this.authLoginUrl(redirect || this.safeBrowserReturnUrl()));
    },
    positionNavigateLoader() {
        const slot = document.querySelector('.starter-slot-area');
        const rect = slot?.getBoundingClientRect();

        if (! rect) return;

        const viewportWidth = document.documentElement.clientWidth;
        const root = document.documentElement;

        root.style.setProperty('--starter-loader-top', `${Math.max(rect.top, 0)}px`);
        root.style.setProperty('--starter-loader-right', `${Math.max(viewportWidth - rect.right, 0)}px`);
        root.style.setProperty('--starter-loader-bottom', '0px');
        root.style.setProperty('--starter-loader-left', `${Math.max(rect.left, 0)}px`);
    },
    extractRedirectUrl(body) {
        try {
            const parsed = JSON.parse(body);

            return parsed?.redirect || null;
        } catch (error) {
            return null;
        }
    },
    isInternalBrowserUrl(url) {
        const path = new URL(url, window.location.href).pathname
            .replace(/^\/+|\/+$/g, '')
            .toLowerCase();

        return /^livewire(?:-[^/]+)?(?:\/|$)/.test(path)
            || ['auth', 'auth/login', 'auth/logout', 'confirm-password', 'lock-screen', 'session/activity', 'up'].includes(path)
            || /^(?:_debugbar|_ignition|api|broadcasting\/auth|horizon|sanctum\/csrf-cookie|telescope)(?:\/|$)/.test(path);
    },
    safeBrowserReturnUrl() {
        return this.isInternalBrowserUrl(window.location.href) ? null : window.location.href;
    },
    isAuthenticationHtmlResponse(response, body) {
        const contentType = String(response?.headers?.get?.('content-type') || '').toLowerCase();
        const html = contentType.includes('text/html') || /^\s*(?:<!doctype\s+html|<html)/i.test(String(body || ''));

        if (! html) {
            return false;
        }

        const responsePath = new URL(response?.url || window.location.href, window.location.href).pathname;

        return response?.redirected
            || /\/(?:auth\/login|lock-screen)\/?$/i.test(responsePath)
            || String(body || '').includes('data-starter-session-expired')
            || (
                String(body || '').includes('meta name="starter-auth-login-url"')
                && ! String(body || '').includes('meta name="starter-lock-screen-url"')
            );
    },
    removeLivewireErrorDialog() {
        const dialog = document.getElementById('livewire-error');

        if (! dialog) {
            return;
        }

        if (dialog.open && typeof dialog.close === 'function') {
            dialog.close();
        }

        dialog.remove();
        document.body.style.overflow = '';
    },
    prepareForTerminalNavigation(sourceRequest = null) {
        if (this.terminalNavigationStarted) {
            return false;
        }

        this.terminalNavigationStarted = true;
        this.autoLocking = true;
        clearTimeout(this.autoLockTimer);
        this.autoLockTouchController?.abort();

        (this.activeLivewireRequests || new Set()).forEach((request) => {
            if (request !== sourceRequest) {
                request.cancel?.();
            }
        });

        this.removeLivewireErrorDialog();
        this.clearLivewireLoader();
        this.showNavigateLoader();

        return true;
    },
    beginTopLevelNavigation(url, { replace = false, sourceRequest = null } = {}) {
        if (! url || ! this.prepareForTerminalNavigation(sourceRequest)) {
            return false;
        }

        if (replace) {
            window.location.replace(url);
        } else {
            window.location.assign(url);
        }

        return true;
    },
    navigate(url) {
        if (! url) return;

        const target = new URL(url, window.location.href);
        const current = new URL(window.location.href);

        if (this.isSameNavigationUrl(target.href, current.href)) {
            this.showNavigateLoader();
            window.location.reload();
            return;
        }

        if (target.origin === current.origin && window.Livewire && typeof window.Livewire.navigate === 'function') {
            this.showNavigateLoader();
            window.Livewire.navigate(target.href);
            return;
        }

        this.showNavigateLoader();
        window.location.assign(target.href);
    },
    closeOpenMenus() {
        document.querySelectorAll('[data-starter-details][open]').forEach((element) => {
            element.removeAttribute('open');
        });

        this.closeAppSwitchers();

        this.themeAdapter().closeNavigation?.();
    },
    fillGeneratedPassword(detail = {}) {
        if (typeof detail.password !== 'string' || detail.password === '') {
            return;
        }

        const form = document.querySelector('[data-starter-password-form]');
        const password = form?.querySelector('#profile-new-password');
        const confirmation = form?.querySelector('#profile-password-confirmation');
        const valueSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;

        [password, confirmation].filter(Boolean).forEach((input) => {
            valueSetter?.call(input, detail.password);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    },
    disposeTheme() {
        document.querySelectorAll('[data-starter-details][open]').forEach((element) => {
            element.removeAttribute('open');
        });

        this.closeAppSwitchers();

        this.themeAdapter().dispose?.();
    },
    closeAppSwitchers(except = null) {
        document.querySelectorAll('[data-starter-app-switcher]').forEach((switcher) => {
            if (switcher === except) {
                return;
            }

            switcher.classList.remove('show');
            switcher.querySelector('[data-starter-app-toggle]')?.setAttribute('aria-expanded', 'false');
            const menu = switcher.querySelector('[data-starter-app-menu]');

            menu?.classList.remove('show');
            this.themeAdapter().closeAppMenu?.(menu);
        });
    },
    toggleAppSwitcher(toggle) {
        const switcher = toggle.closest('[data-starter-app-switcher]');
        const menu = switcher?.querySelector('[data-starter-app-menu]');

        if (! switcher || ! menu) {
            return;
        }

        const isOpen = menu.classList.contains('show');

        this.closeAppSwitchers(switcher);
        switcher.classList.toggle('show', ! isOpen);
        menu.classList.toggle('show', ! isOpen);
        toggle.setAttribute('aria-expanded', String(! isOpen));

        if (isOpen) {
            this.themeAdapter().closeAppMenu?.(menu);
        } else {
            this.themeAdapter().openAppMenu?.(menu);
        }
    },
    prepareTheme() {
        this.themeAdapter().prepare?.();
    },
    activateNavigation() {
        const navigations = Array.from(document.querySelectorAll('[data-starter-navigation]'));

        document.querySelectorAll('[data-starter-menu-url]').forEach((link) => {
            link.classList.remove('active');
            link.removeAttribute('data-current');
            link.closest('[data-starter-menu-item]')?.classList.remove('active');
        });

        navigations.forEach((navigation) => {
            navigation.querySelectorAll('[data-starter-navigation-details][open]').forEach((detail) => {
                detail.removeAttribute('open');
            });
        });

        const activeLinks = Array.from(document.querySelectorAll('[data-starter-menu-url]'))
            .filter((link) => this.isSameUrl(link.dataset.starterMenuUrl));

        activeLinks.forEach((activeLink) => {
            activeLink.classList.add('active');
            activeLink.setAttribute('data-current', 'true');
            activeLink.closest('[data-starter-menu-item]')?.classList.add('active');

            const navigation = activeLink.closest('[data-starter-navigation]');

            if (! navigation) {
                return;
            }

            let detail = activeLink.closest('[data-starter-navigation-details]');

            while (detail && navigation.contains(detail)) {
                if (! detail.classList.contains('starter-horizontal-details')) {
                    detail.setAttribute('open', '');
                }
                detail.closest('[data-starter-menu-item]')?.classList.add('active');
                detail = detail.parentElement?.closest('[data-starter-navigation-details]') ?? null;
            }
        });
    },
    activateAppSwitcher() {
        const links = Array.from(document.querySelectorAll('[data-starter-app-link]'));
        const activeLink = links.find((link) => link.dataset.starterAppHost === window.location.hostname)
            || links.find((link) => this.isSameUrl(link.href));

        links.forEach((link) => link.classList.remove('bg-primary-lt', 'text-primary'));

        if (! activeLink) {
            return;
        }

        activeLink.classList.add('bg-primary-lt', 'text-primary');

        document.querySelectorAll('[data-starter-current-app-name]').forEach((element) => {
            element.textContent = activeLink.dataset.starterAppName || 'App';
        });
    },
    updateAccountSummary(detail = {}) {
        document.querySelectorAll('[data-starter-account-summary]').forEach((summary) => {
            const avatar = summary.querySelector('[data-starter-account-avatar]');
            const name = summary.querySelector('[data-starter-account-name]');
            const role = summary.querySelector('[data-starter-account-role]');

            if (detail.avatarUrl && avatar) {
                avatar.style.backgroundImage = `url("${String(detail.avatarUrl).replace(/"/g, '\\"')}")`;
            }

            if (detail.name && name) {
                name.textContent = detail.name;
            }

            if (detail.roleName && role) {
                role.textContent = detail.roleName;
            }
        });
    },
    updateClientBranding(detail = {}) {
        document.querySelectorAll('[data-starter-brand-logo]').forEach((image) => {
            const fallbackUrl = image.dataset.fallbackSrc;
            const logoUrl = detail.logoUrl || fallbackUrl;

            if (logoUrl) {
                image.src = logoUrl;
            }

            image.alt = detail.clientName || image.alt;
            image.toggleAttribute('data-company-logo', Boolean(detail.logoUrl));
        });
    },
    prepareClientBranding() {
        document.querySelectorAll('[data-starter-brand-logo]').forEach((image) => {
            if (image.dataset.starterBrandBound === 'true') {
                return;
            }

            image.dataset.starterBrandBound = 'true';
            const useFallback = () => {
                const fallbackUrl = image.dataset.fallbackSrc;

                if (fallbackUrl && image.src !== fallbackUrl) {
                    image.src = fallbackUrl;
                    image.removeAttribute('data-company-logo');
                }
            };

            image.addEventListener('error', useFallback);

            if (image.complete && image.naturalWidth === 0) {
                useFallback();
            }
        });
    },
    toastStack() {
        let stack = document.querySelector('[data-starter-toast-stack]');

        if (! stack) {
            stack = document.createElement('div');
            stack.className = 'starter-toast-stack';
            stack.setAttribute('data-starter-toast-stack', '');
            stack.setAttribute('aria-live', 'polite');
            stack.setAttribute('aria-atomic', 'false');
            document.body.appendChild(stack);
        }

        return stack;
    },
    normalizeToastType(type) {
        return ['success', 'info', 'warning', 'danger', 'error'].includes(type) ? (type === 'error' ? 'danger' : type) : 'info';
    },
    toastIcon(type) {
        const icons = {
            success: '<path d="M5 12l5 5l10 -10"></path>',
            info: '<path d="M12 10v6"></path><path d="M12 7h.01"></path>',
            warning: '<path d="M12 7v6"></path><path d="M12 17h.01"></path>',
            danger: '<path d="M8 8l8 8"></path><path d="M16 8l-8 8"></path>',
        };

        return `<svg xmlns="http://www.w3.org/2000/svg" class="icon m-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[type]}</svg>`;
    },
    toast(detail = {}) {
        const payload = typeof detail === 'string' ? { message: detail } : (detail || {});
        const type = this.normalizeToastType(String(payload.type || payload.status || 'info'));
        const title = payload.title || {
            success: 'Berhasil',
            info: 'Informasi',
            warning: 'Peringatan',
            danger: 'Error',
        }[type];
        const message = payload.message || payload.text || '';
        const duration = Number(payload.duration ?? payload.timeout ?? 4500);
        const stack = this.toastStack();
        const toast = document.createElement('div');

        toast.className = `starter-toast starter-toast-${type}`;
        toast.setAttribute('data-starter-toast', '');
        toast.setAttribute('role', ['danger', 'warning'].includes(type) ? 'alert' : 'status');
        toast.style.setProperty('--starter-toast-duration', `${Math.max(duration, 1)}ms`);

        const icon = document.createElement('span');
        icon.className = 'starter-toast-icon';
        icon.innerHTML = this.toastIcon(type);

        const body = document.createElement('div');
        body.className = 'starter-toast-body';

        if (title) {
            const titleElement = document.createElement('div');
            titleElement.className = 'starter-toast-title';
            titleElement.textContent = title;
            body.appendChild(titleElement);
        }

        if (message) {
            const messageElement = document.createElement('div');
            messageElement.className = 'starter-toast-message';
            messageElement.textContent = message;
            body.appendChild(messageElement);
        }

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close starter-toast-close';
        close.setAttribute('aria-label', 'Tutup');
        close.setAttribute('data-starter-toast-dismiss', '');

        toast.append(icon, body, close);

        if (duration > 0) {
            const progress = document.createElement('div');
            progress.className = 'starter-toast-progress';
            toast.appendChild(progress);
        }

        stack.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('is-visible'));

        if (duration > 0) {
            toast.starterToastTimer = setTimeout(() => this.removeToast(toast), duration);
        }

        return toast;
    },
    removeToast(toast) {
        if (! toast) return;

        clearTimeout(toast.starterToastTimer);
        toast.classList.add('is-leaving');
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 180);
    },
    consumeFlashToasts() {
        document.querySelectorAll('[data-starter-flash-toast]').forEach((element) => {
            const payload = {
                type: element.dataset.type || 'info',
                message: element.dataset.message || '',
            };

            if (element.dataset.title) {
                payload.title = element.dataset.title;
            }

            if (element.dataset.duration) {
                payload.duration = Number(element.dataset.duration);
            }

            element.remove();
            this.toast(payload);
        });
    },
    autoLockConfig() {
        const enabled = document.querySelector('meta[name="starter-lock-screen-enabled"]')?.content === '1';
        const timeoutSeconds = Number(document.querySelector('meta[name="starter-lock-screen-timeout"]')?.content || 0);
        const lockUrl = document.querySelector('meta[name="starter-lock-screen-url"]')?.content;
        const activityUrl = document.querySelector('meta[name="starter-session-activity-url"]')?.content;
        const activityScope = document.querySelector('meta[name="starter-session-activity-scope"]')?.content;

        if (! document.body?.hasAttribute('data-starter-app-shell') || ! enabled || timeoutSeconds < 60 || ! lockUrl) {
            return null;
        }

        return {
            activityScope,
            activityUrl,
            lockUrl,
            sessionTouchIntervalMilliseconds: Math.min(60000, Math.max(15000, timeoutSeconds * 1000 / 3)),
            timeoutMilliseconds: Math.min(timeoutSeconds, 86400) * 1000,
        };
    },
    configureAutoLock(navigationCompleted = false) {
        clearTimeout(this.autoLockTimer);
        this.autoLockConfigValue = this.autoLockConfig();
        this.autoLocking = Boolean(this.terminalNavigationStarted);

        if (! this.autoLockConfigValue) {
            return;
        }

        this.lastBrowserActivityAt = Math.max(
            navigationCompleted ? Date.now() : 0,
            this.lastBrowserActivityAt || 0,
            this.sharedBrowserActivityAt(),
        ) || Date.now();
        this.lastSessionTouchAt ??= this.lastBrowserActivityAt;
        this.shareBrowserActivity(this.lastBrowserActivityAt);
        this.scheduleAutoLock();
        this.bindAutoLockActivity();
    },
    autoLockActivityStorageKey() {
        const scope = this.autoLockConfigValue?.activityScope;

        return scope ? `starter:auto-lock:${scope}` : null;
    },
    sharedBrowserActivityAt() {
        const key = this.autoLockActivityStorageKey();

        if (! key) {
            return 0;
        }

        try {
            const timestamp = Number(window.localStorage.getItem(key) || 0);

            return Number.isFinite(timestamp) ? Math.min(timestamp, Date.now()) : 0;
        } catch (error) {
            return 0;
        }
    },
    shareBrowserActivity(timestamp) {
        const key = this.autoLockActivityStorageKey();

        if (! key) {
            return;
        }

        try {
            window.localStorage.setItem(key, String(timestamp));
        } catch (error) {
            // Storage can be unavailable in privacy-restricted browser contexts.
        }
    },
    scheduleAutoLock() {
        clearTimeout(this.autoLockTimer);

        if (! this.autoLockConfigValue || document.hidden) {
            return;
        }

        this.lastBrowserActivityAt = Math.max(this.lastBrowserActivityAt || 0, this.sharedBrowserActivityAt());
        const elapsed = Date.now() - this.lastBrowserActivityAt;
        const remaining = this.autoLockConfigValue.timeoutMilliseconds - elapsed;

        if (remaining <= 0) {
            this.reconcileAutoLock();
            return;
        }

        this.autoLockTimer = setTimeout(
            () => this.reconcileAutoLock(),
            remaining,
        );
    },
    bindAutoLockActivity() {
        if (this.autoLockActivityBound) {
            return;
        }

        ['keydown', 'pointerdown', 'touchstart', 'scroll'].forEach((eventName) => {
            document.addEventListener(eventName, () => this.recordBrowserActivity(), {
                capture: true,
                passive: true,
            });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearTimeout(this.autoLockTimer);
                return;
            }

            this.resumeAutoLock();
        });

        ['focus', 'pageshow'].forEach((eventName) => {
            window.addEventListener(eventName, () => this.resumeAutoLock());
        });

        window.addEventListener('storage', (event) => {
            if (event.key !== this.autoLockActivityStorageKey()) {
                return;
            }

            const timestamp = Number(event.newValue || 0);

            if (Number.isFinite(timestamp) && timestamp > (this.lastBrowserActivityAt || 0)) {
                this.lastBrowserActivityAt = timestamp;
                this.scheduleAutoLock();
            }
        });

        this.autoLockActivityBound = true;
    },
    resumeAutoLock() {
        if (! this.autoLockConfigValue || this.autoLocking || document.hidden) {
            return;
        }

        this.lastBrowserActivityAt = Math.max(this.lastBrowserActivityAt || 0, this.sharedBrowserActivityAt());
        const elapsed = Date.now() - this.lastBrowserActivityAt;

        if (elapsed >= this.autoLockConfigValue.timeoutMilliseconds) {
            this.reconcileAutoLock();
            return;
        }

        this.recordBrowserActivity(true);
    },
    recordBrowserActivity(forceTouch = false) {
        if (! this.autoLockConfigValue || this.autoLocking) {
            return;
        }

        this.lastBrowserActivityAt = Date.now();
        this.shareBrowserActivity(this.lastBrowserActivityAt);
        this.scheduleAutoLock();

        const now = Date.now();

        if (! forceTouch && now - this.lastSessionTouchAt < this.autoLockConfigValue.sessionTouchIntervalMilliseconds) {
            return;
        }

        this.touchSessionActivity();
    },
    async touchSessionActivity() {
        const activityUrl = this.autoLockConfigValue?.activityUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (! activityUrl || ! csrfToken) {
            return 'unavailable';
        }

        if (this.autoLockTouchPromise) {
            return this.autoLockTouchPromise;
        }

        const controller = new AbortController();
        this.autoLockTouchController = controller;
        const abortTimer = setTimeout(() => controller.abort(), 10000);

        this.autoLockTouchPromise = (async () => {
            try {
                const response = await fetch(activityUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Starter-Page-Url': this.safeBrowserReturnUrl() || '',
                    },
                    signal: controller.signal,
                });

                if (response.status === 423) {
                    const body = await response.text();
                    const redirect = this.extractRedirectUrl(body) || this.autoLockConfigValue?.lockUrl;

                    this.beginTopLevelNavigation(redirect, { replace: true });

                    return 'locked';
                }

                if ([401, 419].includes(response.status) || response.redirected) {
                    const body = response.redirected ? '' : await response.text();

                    this.beginTopLevelNavigation(
                        response.redirected
                            ? response.url
                            : (this.extractRedirectUrl(body) || this.authLoginUrl(this.safeBrowserReturnUrl())),
                    );

                    return 'redirected';
                }

                if (! response.ok) {
                    return 'unavailable';
                }

                this.lastSessionTouchAt = Date.now();

                return 'active';
            } catch (error) {
                return 'unavailable';
            } finally {
                clearTimeout(abortTimer);

                if (this.autoLockTouchController === controller) {
                    this.autoLockTouchController = null;
                }
            }
        })();

        try {
            return await this.autoLockTouchPromise;
        } finally {
            this.autoLockTouchPromise = null;
        }
    },
    async reconcileAutoLock() {
        if (! this.autoLockConfigValue || this.autoLocking || this.autoLockReconciling || document.hidden) {
            return;
        }

        const sharedActivityAt = this.sharedBrowserActivityAt();

        if (sharedActivityAt > (this.lastBrowserActivityAt || 0)) {
            this.lastBrowserActivityAt = sharedActivityAt;
            this.scheduleAutoLock();

            return;
        }

        this.autoLockReconciling = true;

        try {
            const status = await this.touchSessionActivity();

            if (status === 'active') {
                this.lastBrowserActivityAt = Date.now();
                this.shareBrowserActivity(this.lastBrowserActivityAt);
                this.scheduleAutoLock();
            } else if (status === 'unavailable') {
                this.performAutoLock();
            }
        } finally {
            this.autoLockReconciling = false;
        }
    },
    performAutoLock() {
        if (! this.autoLockConfigValue || this.autoLocking || this.terminalNavigationStarted) {
            return;
        }

        const lockUrl = new URL(this.autoLockConfigValue.lockUrl, window.location.href);
        const returnUrl = this.safeBrowserReturnUrl();

        if (returnUrl) {
            lockUrl.searchParams.set('redirect', returnUrl);
        }

        lockUrl.searchParams.set('reason', 'idle_timeout');

        // Locking must leave a suspended Livewire navigation behind. A full-page
        // replacement guarantees the server lock state is rendered immediately.
        this.beginTopLevelNavigation(lockUrl.href, { replace: true });
    },
    bind() {
        if (this.bound) return;

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-starter-logout-form]');
            const redirect = form?.querySelector('[data-starter-logout-redirect]');

            if (form) {
                this.prepareForTerminalNavigation();
            }

            if (redirect) {
                redirect.value = window.location.href;
            }
        });

        document.addEventListener('click', (event) => {
            const toastDismiss = event.target.closest('[data-starter-toast-dismiss]');

            if (toastDismiss) {
                this.removeToast(toastDismiss.closest('[data-starter-toast]'));
                return;
            }

            const alertDismiss = event.target.closest('[data-starter-alert-dismiss]');

            if (alertDismiss) {
                alertDismiss.closest('[data-starter-alert]')?.remove();
                return;
            }

            const appToggle = event.target.closest('[data-starter-app-toggle]');

            if (appToggle) {
                event.preventDefault();
                this.toggleAppSwitcher(appToggle);
                return;
            }

            if (! event.target.closest('[data-starter-app-switcher]')) {
                this.closeAppSwitchers();
            }

            document.querySelectorAll('[data-starter-details][open]').forEach((element) => {
                if (! element.contains(event.target)) {
                    element.removeAttribute('open');
                }
            });

            const link = event.target.closest('a[data-starter-navigate]');

            if (! link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target === '_blank') {
                return;
            }

            event.preventDefault();
            this.closeOpenMenus();
            this.navigate(link.href);
        });

        document.addEventListener('starter-account-updated', (event) => {
            this.updateAccountSummary(event.detail || {});
        });

        document.addEventListener('starter-client-branding-updated', (event) => {
            this.updateClientBranding(event.detail || {});
        });

        ['starter-toast', 'toast', 'notify'].forEach((name) => {
            window.addEventListener(name, (event) => this.toast(event.detail || {}));
        });

        window.addEventListener('unhandledrejection', (event) => {
            const message = String(event.reason?.message || event.reason || '');

            if (! this.navigating || ! /NetworkError|Failed to fetch|Load failed/i.test(message)) {
                return;
            }

            event.preventDefault();
            this.redirectToLogin();
        });

        window.addEventListener('resize', () => {
            if (this.navigating || (this.livewireLoadingCount || 0) > 0) {
                this.positionNavigateLoader();
            }
        });

        window.addEventListener('livewire-upload-start', (event) => {
            const componentRoot = event.target?.closest?.('[wire\\:id]');

            this.showLivewireLoader(componentRoot ? [{ el: componentRoot }] : []);
        });

        ['livewire-upload-finish', 'livewire-upload-error', 'livewire-upload-cancel'].forEach((eventName) => {
            window.addEventListener(eventName, () => this.hideLivewireLoader());
        });

        this.bound = true;
    },
    bindLivewire() {
        if (this.livewireBound || ! window.Livewire?.interceptRequest) return;

        this.activeLivewireRequests ||= new Set();

        window.Livewire.interceptRequest(({ request, onSend, onError, onRedirect, onFinish }) => {
            const messages = Array.from(request?.messages || []);
            const passive = messages.length > 0 && messages.every((message) => this.isPassiveLivewireMessage(message));
            const actionMessages = messages.filter((message) => (
                Array.isArray(message.calls)
                && message.calls.length > 0
                && ! this.isSilentLivewireMessage(message)
            ));
            const components = actionMessages
                .map((message) => message.component)
                .filter(Boolean);
            let started = false;

            request.options.headers['X-Starter-Page-Url'] = window.location.href;

            if (passive) {
                request.options.headers['X-Starter-Passive'] = '1';
            }

            if (this.terminalNavigationStarted) {
                request.cancel();

                return;
            }

            onSend(() => {
                this.activeLivewireRequests.add(request);

                if (actionMessages.length === 0) {
                    return;
                }

                started = true;
                this.showLivewireLoader(components);
            });

            onError(({ response, body, preventDefault }) => {
                const authenticationHtml = this.isAuthenticationHtmlResponse(response, body);

                if (![401, 419, 423].includes(response.status) && ! authenticationHtml) {
                    return;
                }

                const redirect = this.extractRedirectUrl(body)
                    || (response.status === 423 ? this.autoLockConfigValue?.lockUrl : null)
                    || this.authLoginUrl(this.safeBrowserReturnUrl());

                preventDefault();
                this.beginTopLevelNavigation(redirect, {
                    replace: response.status === 423,
                    sourceRequest: request,
                });
            });

            onRedirect(({ url, preventDefault }) => {
                preventDefault();
                this.beginTopLevelNavigation(url, { sourceRequest: request });
            });

            onFinish(() => {
                this.activeLivewireRequests.delete(request);

                if (started) {
                    this.hideLivewireLoader();
                }
            });
        });

        this.livewireBound = true;
    },
    init(navigationCompleted = false) {
        this.bind();
        this.bindLivewire();
        this.activateNavigation();
        this.activateAppSwitcher();
        this.prepareTheme();
        this.prepareClientBranding();
        this.consumeFlashToasts();
        this.configureAutoLock(navigationCompleted);
    },
});

document.addEventListener('DOMContentLoaded', () => window.StarterTemplate.init(true));
document.addEventListener('livewire:initialized', () => window.StarterTemplate.bindLivewire());
document.addEventListener('livewire:navigate', () => window.StarterTemplate.showNavigateLoader());
document.addEventListener('livewire:navigating', () => window.StarterTemplate.disposeTheme());
document.addEventListener('livewire:navigated', () => {
    window.StarterTemplate.hideNavigateLoader();
    window.StarterTemplate.clearLivewireLoader();
    window.StarterTemplate.init(true);
});
window.addEventListener('pageshow', (event) => {
    window.StarterTemplate.hideNavigateLoader();
    window.StarterTemplate.clearLivewireLoader();
    window.StarterTemplate.init(! event.persisted);
});
