window.StarterThemeAdapter = Object.assign(window.StarterThemeAdapter || {}, {
    bootstrap() {
        return window.bootstrap || window.tabler?.bootstrap || null;
    },
    closeNavigation() {
        document.querySelectorAll('[data-starter-navigation-collapse].show').forEach((element) => {
            const instance = this.bootstrap()?.Collapse?.getInstance(element);
            instance ? instance.hide() : element.classList.remove('show');
        });
    },
    closeAppMenu(menu) {
        menu?.removeAttribute('data-bs-popper');
    },
    openAppMenu(menu) {
        menu?.setAttribute('data-bs-popper', 'static');
    },
    dispose() {
        document.querySelectorAll('.dropdown-menu.show').forEach((element) => element.classList.remove('show'));
        document.querySelectorAll('.dropdown-toggle[aria-expanded="true"]').forEach((element) => {
            element.setAttribute('aria-expanded', 'false');
        });

        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach((element) => {
            this.bootstrap()?.Collapse?.getInstance(element)?.dispose();
        });

        document.querySelectorAll('.collapse.show').forEach((element) => element.classList.remove('show'));
    },
    prepare() {
        const bootstrap = this.bootstrap();

        if (! bootstrap?.Dropdown) return;

        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((element) => {
            bootstrap.Dropdown.getOrCreateInstance(element);
        });
    },
});
