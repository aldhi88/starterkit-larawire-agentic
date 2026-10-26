(() => {
    if (window.StarterCms) {
        // Recover tabs running an older singleton after a Livewire asset update.
        if (typeof window.StarterCms.categoryPicker !== 'function') {
            window.location.reload();
            return;
        }
        window.StarterCms.init();
        return;
    }

    const editors = new Map();
    const trees = new Map();
    const categoryOrders = new Map();
    const pickers = new Map();
    const postDialogs = new Map();
    const publicationPickers = new Map();
    let observer;
    let previousPickerOverflow = null;
    const owner = (element) => element.closest('[wire\\:id]');
    const wireFor = (element) => {
        const component = owner(element);
        return component && window.Livewire ? window.Livewire.find(component.getAttribute('wire:id')) : null;
    };
    const announce = (root, message, error = false) => {
        const status = root.querySelector('[data-cms-editor-status]');
        if (status) {
            status.textContent = message;
            status.setAttribute('role', error ? 'alert' : 'status');
        }
    };
    const pickerFor = (componentId) => Array.from(document.querySelectorAll('[data-cms-picker]')).find((element) => (element.dataset.cmsPickerOwner || owner(element)?.getAttribute('wire:id')) === componentId);
    const emitUpload = (root, type, detail = {}) => root.dispatchEvent(new CustomEvent(`cms-upload-${type}`, { bubbles: true, detail }));

    function uploadFiles(wire, files, field, root, complete) {
        const accepted = Array.from(files || []);
        if (! accepted.length || ! wire) return;
        if (accepted.length > 20 || accepted.some((file) => file.size > 10 * 1024 * 1024 || ! ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'].includes(file.type))) {
            emitUpload(root, 'error', { message: 'Pilih maksimal 20 gambar atau PDF, masing-masing maksimal 10 MB.' });
            return;
        }
        emitUpload(root, 'start');
        wire.$uploadMultiple(field, accepted, async () => {
            try {
                if (complete) await complete();
                emitUpload(root, 'finish');
            } catch {
                emitUpload(root, 'error', { message: 'Unggahan belum selesai. Periksa file lalu coba kembali.' });
            }
        }, () => emitUpload(root, 'error', { message: 'File gagal diunggah. Coba kembali.' }), (event) => emitUpload(root, 'progress', { progress: event.detail.progress }));
    }

    function imageDimensions(requestedWidth, naturalWidth, naturalHeight) {
        const ratio = naturalWidth > 0 && naturalHeight > 0 ? naturalHeight / naturalWidth : 1;
        const maximumWidth = Math.max(1, Math.min(10000, Math.floor(10000 / ratio)));
        const width = Math.max(1, Math.min(maximumWidth, Math.round(Number(requestedWidth) || 1)));
        return { width, height: Math.max(1, Math.min(10000, Math.round(width * ratio))) };
    }

    function imageResize(root, quill, sync) {
        const canvas = root.querySelector('[data-cms-editor-canvas]');
        const overlay = root.querySelector('[data-cms-image-overlay]');
        const controls = root.querySelector('[data-cms-image-controls]');
        const input = root.querySelector('[data-cms-image-width]');
        const handle = root.querySelector('[data-cms-image-handle]');
        const readout = root.querySelector('[data-cms-image-size]');
        if (! canvas || ! overlay || ! controls || ! input || ! handle) return { hide() {}, destroy() {} };
        const idleHelp = root.querySelector('[data-cms-editor-status]')?.textContent || '';
        let selected = null;
        let dragging = null;
        let frame = null;
        const naturalSize = () => ({
            width: selected?.naturalWidth || Number(selected?.getAttribute('width')) || selected?.getBoundingClientRect().width || 1,
            height: selected?.naturalHeight || Number(selected?.getAttribute('height')) || selected?.getBoundingClientRect().height || 1,
        });
        const position = () => {
            frame = null;
            if (! selected || ! quill.root.contains(selected)) { hide(); return; }
            const image = selected.getBoundingClientRect();
            const bounds = canvas.getBoundingClientRect();
            overlay.style.left = `${image.left - bounds.left + canvas.scrollLeft}px`;
            overlay.style.top = `${image.top - bounds.top + canvas.scrollTop}px`;
            overlay.style.width = `${image.width}px`;
            overlay.style.height = `${image.height}px`;
            if (root.closest('[data-cms-tabler-post-form]')) {
                const viewport = quill.root.getBoundingClientRect();
                overlay.style.clipPath = `inset(${Math.max(-16, viewport.top - image.top)}px ${Math.max(-16, image.right - viewport.right)}px ${Math.max(-16, image.bottom - viewport.bottom)}px ${Math.max(-16, viewport.left - image.left)}px)`;
            }
        };
        const schedule = () => { if (selected && frame === null) frame = requestAnimationFrame(position); };
        const updateReadout = () => {
            if (! selected) return;
            const width = Number(selected.getAttribute('width')) || Math.round(selected.getBoundingClientRect().width);
            const size = naturalSize();
            const height = Number(selected.getAttribute('height')) || Math.round(width * size.height / size.width);
            input.value = width;
            if (readout) readout.textContent = `${width} × ${height} px. Proporsi gambar tetap terjaga.`;
        };
        const format = (attributes, source = 'user') => {
            if (! selected || ! quill.root.contains(selected)) { hide(); return; }
            const blot = window.Quill.find(selected);
            if (! blot) return;
            quill.formatText(quill.getIndex(blot), 1, attributes, source);
            updateReadout();
            schedule();
        };
        const resize = (width, source = 'user') => {
            const size = dragging?.natural || naturalSize();
            const dimensions = imageDimensions(width, size.width, size.height);
            format({ width: String(dimensions.width), height: String(dimensions.height) }, source);
            return dimensions;
        };
        const endDrag = (commit = true) => {
            if (! dragging) return;
            const previous = dragging;
            dragging = null;
            const size = selected ? { width: selected.getAttribute('width'), height: selected.getAttribute('height') } : null;
            if (handle.hasPointerCapture?.(previous.pointerId)) handle.releasePointerCapture(previous.pointerId);
            root.classList.remove('is-resizing-image');
            if (! selected) return;
            format({ width: previous.width || false, height: previous.height || false }, 'silent');
            if (commit && size) format(size, 'user');
            sync();
        };
        function hide() {
            endDrag(false);
            const wasSelected = Boolean(selected);
            selected = null;
            overlay.hidden = true;
            controls.hidden = true;
            input.value = '';
            if (frame !== null) cancelAnimationFrame(frame);
            frame = null;
            if (wasSelected && idleHelp) announce(root, idleHelp);
        }
        const select = (image) => {
            if (! image || ! quill.root.contains(image)) return;
            if (selected !== image) endDrag(false);
            selected = image;
            overlay.hidden = false;
            controls.hidden = false;
            updateReadout();
            schedule();
            announce(root, 'Gambar dipilih. Atur ukurannya di bawah editor atau tarik sudut gambar.');
        };
        const imageAtCursor = (range) => {
            if (! range) return null;
            for (const index of [range.index, range.index + 1]) {
                const [leaf] = quill.getLeaf(Math.min(index, quill.getLength() - 1));
                if (leaf?.domNode?.tagName === 'IMG') return leaf.domNode;
            }
            return null;
        };
        const selection = (range, previousRange, source) => {
            if (source !== 'user' || ! range || dragging || controls.contains(document.activeElement) || overlay.contains(document.activeElement)) return;
            const image = imageAtCursor(range);
            if (image) select(image);
            else hide();
        };
        const click = (event) => {
            const image = event.target.closest('img');
            if (image && quill.root.contains(image)) select(image);
        };
        const outside = (event) => {
            if (! selected || controls.contains(event.target) || overlay.contains(event.target) || event.target === selected) return;
            hide();
        };
        const change = () => {
            if (! selected) return;
            if (! Number.isFinite(Number(input.value)) || Number(input.value) < 1) {
                announce(root, 'Masukkan lebar gambar antara 1 dan 10.000 piksel.', true);
                return;
            }
            const dimensions = resize(Number(input.value));
            if (dimensions) announce(root, `Ukuran gambar ${dimensions.width} × ${dimensions.height} piksel.`);
        };
        const controlClick = (event) => {
            const button = event.target.closest('button');
            if (! button || ! selected) return;
            if (button.hasAttribute('data-cms-image-apply')) { change(); return; }
            if (button.hasAttribute('data-cms-image-done')) { hide(); quill.focus(); return; }
            if (button.hasAttribute('data-cms-image-original')) { resize(naturalSize().width); return; }
            if (button.dataset.cmsImagePercent) {
                const style = window.getComputedStyle(quill.root);
                const available = quill.root.clientWidth - parseFloat(style.paddingLeft || 0) - parseFloat(style.paddingRight || 0);
                resize(Math.max(1, available) * Number(button.dataset.cmsImagePercent) / 100);
            }
        };
        const inputKey = (event) => {
            if (event.key === 'Enter') { event.preventDefault(); event.stopPropagation(); change(); }
            if (event.key === 'Escape') { event.preventDefault(); hide(); quill.focus(); }
        };
        const pointerDown = (event) => {
            if (! selected || event.button !== 0) return;
            event.preventDefault();
            dragging = { pointerId: event.pointerId, x: event.clientX, startWidth: selected.getBoundingClientRect().width,
                width: selected.getAttribute('width'), height: selected.getAttribute('height'), natural: naturalSize() };
            handle.setPointerCapture(event.pointerId);
            root.classList.add('is-resizing-image');
            quill.history.cutoff();
        };
        const pointerMove = (event) => {
            if (! dragging || event.pointerId !== dragging.pointerId) return;
            event.preventDefault();
            resize(dragging.startWidth + event.clientX - dragging.x, 'silent');
        };
        const pointerUp = (event) => { if (dragging?.pointerId === event.pointerId) { event.preventDefault(); endDrag(true); quill.history.cutoff(); } };
        const pointerCancel = () => endDrag(false);
        const handleKey = (event) => {
            if (event.key === 'Escape') { event.preventDefault(); hide(); quill.focus(); return; }
            if (! ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key) || ! selected) return;
            event.preventDefault();
            const current = Number(selected.getAttribute('width')) || selected.getBoundingClientRect().width;
            resize(current + (['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1) * (event.shiftKey ? 1 : 10));
        };
        const contentChange = () => { if (selected) { if (! quill.root.contains(selected)) hide(); else { updateReadout(); schedule(); } } };
        quill.root.addEventListener('click', click);
        quill.root.addEventListener('load', schedule, true);
        document.addEventListener('pointerdown', outside, true);
        document.addEventListener('scroll', schedule, true);
        window.addEventListener('resize', schedule);
        const viewportObserver = new ResizeObserver(schedule);
        viewportObserver.observe(quill.root);
        controls.addEventListener('click', controlClick);
        input.addEventListener('keydown', inputKey);
        handle.addEventListener('pointerdown', pointerDown);
        handle.addEventListener('pointermove', pointerMove);
        handle.addEventListener('pointerup', pointerUp);
        handle.addEventListener('pointercancel', pointerCancel);
        handle.addEventListener('lostpointercapture', pointerCancel);
        handle.addEventListener('keydown', handleKey);
        quill.on('selection-change', selection);
        quill.on('text-change', contentChange);
        return {
            hide,
            destroy() {
                hide();
                quill.root.removeEventListener('click', click);
                quill.root.removeEventListener('load', schedule, true);
                document.removeEventListener('pointerdown', outside, true);
                document.removeEventListener('scroll', schedule, true);
                window.removeEventListener('resize', schedule);
                viewportObserver.disconnect();
                controls.removeEventListener('click', controlClick);
                input.removeEventListener('keydown', inputKey);
                handle.removeEventListener('pointerdown', pointerDown);
                handle.removeEventListener('pointermove', pointerMove);
                handle.removeEventListener('pointerup', pointerUp);
                handle.removeEventListener('pointercancel', pointerCancel);
                handle.removeEventListener('lostpointercapture', pointerCancel);
                handle.removeEventListener('keydown', handleKey);
                quill.off('selection-change', selection);
                quill.off('text-change', contentChange);
            },
        };
    }

    function editorScrollbars(root, quill, componentId) {
        if (! root.closest('[data-cms-tabler-post-form]')) return { destroy() {} };
        const canvas = root.querySelector('[data-cms-editor-canvas]');
        const content = quill.root;
        content.id = `cms-editor-content-${componentId}`;
        canvas.classList.add('cms-external-scrollbars');
        let frame = 0;
        const bars = ['vertical', 'horizontal'].map((axis) => {
            const vertical = axis === 'vertical';
            const track = document.createElement('div');
            const thumb = document.createElement('span');
            track.className = 'cms-editor-scrollbar';
            track.dataset.axis = axis;
            track.setAttribute('role', 'scrollbar');
            track.setAttribute('aria-controls', content.id);
            track.setAttribute('aria-orientation', axis);
            track.setAttribute('aria-label', vertical ? 'Gulir isi editor ke atas atau bawah' : 'Gulir isi editor ke kiri atau kanan');
            track.setAttribute('aria-valuemin', '0');
            thumb.className = 'cms-editor-scrollbar-thumb';
            track.append(thumb);
            canvas.append(track);
            const offset = vertical ? 'scrollTop' : 'scrollLeft';
            const size = vertical ? 'clientHeight' : 'clientWidth';
            const extent = vertical ? 'scrollHeight' : 'scrollWidth';
            let max = 0, travel = 0, start = null;
            const point = event => vertical ? event.clientY : event.clientX;
            const update = () => {
                max = Math.max(0, content[extent] - content[size]);
                track.setAttribute('aria-disabled', String(max <= 1));
                track.tabIndex = max > 1 ? 0 : -1;
                const length = track[size];
                const thumbSize = Math.min(length, Math.max(24, length * content[size] / Math.max(1, content[extent])));
                travel = Math.max(0, length - thumbSize);
                thumb.style[vertical ? 'height' : 'width'] = `${thumbSize}px`;
                thumb.style.transform = `translate${vertical ? 'Y' : 'X'}(${max ? content[offset] / max * travel : 0}px)`;
                track.setAttribute('aria-valuemax', String(Math.round(max)));
                track.setAttribute('aria-valuenow', String(Math.round(content[offset])));
            };
            const down = event => {
                if (max <= 1 || event.button !== 0) return;
                event.preventDefault();
                track.focus({ preventScroll: true });
                const bounds = thumb.getBoundingClientRect();
                const before = point(event) < (vertical ? bounds.top : bounds.left);
                const after = point(event) > (vertical ? bounds.bottom : bounds.right);
                if (before || after) content[offset] += (before ? -1 : 1) * content[size] * .9;
                start = { point: point(event), offset: content[offset] };
                track.setPointerCapture(event.pointerId);
            };
            const move = event => {
                if (start && travel) content[offset] = start.offset + (point(event) - start.point) / travel * max;
            };
            const up = () => { start = null; };
            const keydown = event => {
                const keys = { ArrowUp: -40, ArrowLeft: -40, ArrowDown: 40, ArrowRight: 40, PageUp: -content[size], PageDown: content[size], Home: -max, End: max };
                if (! Object.hasOwn(keys, event.key)) return;
                event.preventDefault();
                content[offset] += keys[event.key];
            };
            const listeners = { pointerdown: down, pointermove: move, pointerup: up, pointercancel: up, lostpointercapture: up, keydown };
            Object.entries(listeners).forEach(([name, handler]) => track.addEventListener(name, handler));
            return { update, destroy() {
                Object.entries(listeners).forEach(([name, handler]) => track.removeEventListener(name, handler));
                track.remove();
            } };
        });
        const schedule = () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => bars.forEach(bar => bar.update()));
        };
        const observer = new ResizeObserver(schedule);
        observer.observe(content);
        content.addEventListener('scroll', schedule, { passive: true });
        content.addEventListener('load', schedule, true);
        quill.on('text-change', schedule);
        schedule();
        return { destroy() {
            cancelAnimationFrame(frame);
            observer.disconnect();
            content.removeEventListener('scroll', schedule);
            content.removeEventListener('load', schedule, true);
            quill.off('text-change', schedule);
            bars.forEach(bar => bar.destroy());
            canvas.classList.remove('cms-external-scrollbars');
        } };
    }

    function editorViewport(root, quill, resizer, componentId) {
        const button = root.querySelector('[data-cms-editor-expand]');
        if (! button) return { destroy() {} };
        let previousOverflow = null;
        const setExpanded = (expanded) => {
            resizer.hide();
            root.classList.toggle('cms-editor-expanded', expanded);
            const label = expanded ? 'Kembalikan ukuran editor' : 'Perbesar editor';
            button.setAttribute('aria-pressed', String(expanded));
            button.setAttribute('aria-label', label);
            button.title = label;
            button.querySelector('path')?.setAttribute('d', expanded
                ? 'M3 9h6V3M15 3v6h6M21 15h-6v6M9 21v-6H3'
                : 'M9 3H3v6M15 3h6v6M21 15v6h-6M3 15v6h6');
            if (expanded) {
                previousOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                root.setAttribute('role', 'dialog');
                root.setAttribute('aria-modal', 'true');
                root.setAttribute('aria-label', 'Editor postingan');
            } else {
                if (previousOverflow !== null) document.body.style.overflow = previousOverflow;
                previousOverflow = null;
                root.removeAttribute('role');
                root.removeAttribute('aria-modal');
                root.removeAttribute('aria-label');
            }
        };
        const toggle = () => setExpanded(! root.classList.contains('cms-editor-expanded'));
        const keydown = (event) => {
            if (! root.classList.contains('cms-editor-expanded') || pickerFor(componentId)) return;
            if (event.key === 'Escape') { event.preventDefault(); setExpanded(false); button.focus(); return; }
            if (event.key !== 'Tab') return;
            const fields = Array.from(root.querySelectorAll('button,input,select,a[href],[tabindex="0"],[contenteditable="true"]'))
                .filter((element) => ! element.disabled && element.getBoundingClientRect().width > 0);
            const first = fields[0];
            const last = fields[fields.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (! event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        };
        const focusin = (event) => {
            if (root.classList.contains('cms-editor-expanded') && ! pickerFor(componentId) && ! root.contains(event.target)) quill.focus();
        };
        button.addEventListener('click', toggle);
        root.addEventListener('keydown', keydown);
        document.addEventListener('focusin', focusin);
        return { destroy() {
            if (root.classList.contains('cms-editor-expanded')) setExpanded(false);
            button.removeEventListener('click', toggle);
            root.removeEventListener('keydown', keydown);
            document.removeEventListener('focusin', focusin);
        } };
    }

    function initEditor(root) {
        if (editors.has(root) || ! window.Quill) return;
        const wire = wireFor(root);
        if (! wire) return;
        const surface = root.querySelector('[data-cms-editor-surface]');
        const initial = root.querySelector('[data-cms-editor-initial]');
        if (! surface || ! initial) return;
        const icons = window.Quill.import('ui/icons');
        icons.undo = '<svg viewBox="0 0 18 18"><path class="ql-stroke" d="M6 4L2 8l4 4M3 8h8a4 4 0 0 1 0 8"/></svg>';
        icons.redo = '<svg viewBox="0 0 18 18"><path class="ql-stroke" d="M12 4l4 4-4 4m3-4H7a4 4 0 0 0 0 8"/></svg>';
        let lastRange = { index: 0, length: 0 };
        let uploading = false;
        const componentId = owner(root).getAttribute('wire:id');
        const quill = new window.Quill(surface, {
            theme: 'snow',
            placeholder: 'Mulai menulis konten Anda…',
            formats: ['header', 'size', 'bold', 'italic', 'underline', 'strike', 'color', 'background', 'align', 'indent', 'list', 'blockquote', 'link', 'image'],
            modules: {
                history: { delay: 800, maxStack: 100, userOnly: true },
                toolbar: {
                    container: [
                        ['undo', 'redo'], [{ header: [1, 2, 3, false] }], [{ size: ['small', false, 'large', 'huge'] }],
                        ['bold', 'italic', 'underline', 'strike'], [{ color: [] }, { background: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }], [{ indent: '-1' }, { indent: '+1' }],
                        [{ align: [] }], ['blockquote', 'link', 'clean'],
                    ],
                    handlers: {
                        undo() { this.quill.history.undo(); },
                        redo() { this.quill.history.redo(); },
                    },
                },
            },
        });
        const toolbarMedia = root.querySelector('[data-cms-toolbar-media]');
        if (toolbarMedia) {
            const toolbar = root.querySelector('.ql-toolbar');
            const group = document.createElement('span');
            group.className = 'ql-formats';
            group.append(toolbarMedia.content.cloneNode(true));
            toolbar?.append(group);
        }
        quill.setContents(quill.clipboard.convert({ html: initial.value || '' }), 'silent');
        quill.root.setAttribute('role', 'textbox');
        quill.root.setAttribute('aria-label', 'Isi konten');
        quill.root.setAttribute('aria-multiline', 'true');
        const Delta = window.Quill.import('delta');
        const mediaBase = new URL(root.dataset.cmsMediaBase, window.location.href);
        const mediaPrefix = mediaBase.pathname.slice(0, -26);
        quill.clipboard.addMatcher('IMG', (node, delta) => {
            try {
                const url = new URL(node.getAttribute('src') || '', window.location.href);
                const id = url.pathname.match(/([0-9A-HJKMNP-TV-Z]{26})$/i)?.[1];
                return id && url.origin === mediaBase.origin && url.pathname === mediaPrefix + id && ! url.username && ! url.password && ! url.search && ! url.hash ? delta : new Delta();
            } catch { return new Delta(); }
        });
        const sync = () => {
            initial.value = quill.getSemanticHTML();
            initial.defaultValue = initial.value;
            wire.$set('form.body', initial.value, false);
            wire.$set('form.body_format', 'html', false);
        };
        quill.on('text-change', sync);
        quill.on('selection-change', (range) => { if (range) lastRange = range; });
        const labels = { undo: 'Urungkan', redo: 'Ulangi', bold: 'Tebal', italic: 'Miring', underline: 'Garis bawah', strike: 'Coret', blockquote: 'Kutipan', link: 'Tautan', clean: 'Hapus format', list: 'Daftar', indent: 'Indentasi' };
        root.querySelectorAll('.ql-toolbar button').forEach((button) => {
            const name = Array.from(button.classList).find((item) => item.startsWith('ql-'))?.slice(3);
            const label = labels[name] || name || 'Format';
            button.type = 'button';
            if (! button.hasAttribute('aria-label')) button.setAttribute('aria-label', label);
            if (! button.hasAttribute('title')) button.title = label;
        });
        const pickerLabels = { header: { '1': 'Judul 1', '2': 'Judul 2', '3': 'Judul 3', '': 'Paragraf' }, size: { small: 'Kecil', large: 'Besar', huge: 'Sangat besar', '': 'Normal' } };
        Object.entries(pickerLabels).forEach(([name, values]) => {
            root.querySelectorAll(`.ql-picker.ql-${name} .ql-picker-item,.ql-picker.ql-${name} .ql-picker-label`).forEach((element) => {
                element.dataset.label = values[element.dataset.value || ''] || values[''];
            });
        });
        root.querySelectorAll('.ql-picker-label').forEach((element) => {
            const kind = element.closest('.ql-picker');
            element.setAttribute('aria-label', kind.classList.contains('ql-color') ? 'Warna teks' : kind.classList.contains('ql-background') ? 'Warna latar teks' : kind.classList.contains('ql-align') ? 'Perataan teks' : kind.classList.contains('ql-header') ? 'Gaya paragraf' : 'Ukuran teks');
        });
        const upload = (event) => {
            const files = Array.from((event.clipboardData || event.dataTransfer)?.files || []);
            if (! files.length) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            if (uploading) { announce(root, 'Tunggu unggahan sebelumnya selesai.', true); return; }
            uploadFiles(wire, files, 'editorUploads', root, () => wire.finishEditorUploads());
        };
        const uploadStart = () => { uploading = true; root.classList.add('is-uploading'); announce(root, 'Mengunggah media…'); };
        const uploadProgress = (event) => announce(root, `Mengunggah media ${Math.round(event.detail.progress || 0)}%…`);
        const uploadFinish = () => { uploading = false; root.classList.remove('is-uploading'); announce(root, 'Media siap digunakan.'); };
        const uploadError = (event) => { uploading = false; root.classList.remove('is-uploading'); announce(root, event.detail.message || 'Unggahan gagal.', true); };
        root.addEventListener('cms-upload-start', uploadStart);
        root.addEventListener('cms-upload-progress', uploadProgress);
        root.addEventListener('cms-upload-finish', uploadFinish);
        root.addEventListener('cms-upload-error', uploadError);
        quill.root.addEventListener('paste', upload, true);
        quill.root.addEventListener('drop', upload, true);
        const mediaButton = root.querySelector('[data-cms-open-media]');
        const openMedia = () => openPicker('all');
        mediaButton?.addEventListener('click', openMedia);
        const resizer = imageResize(root, quill, sync);
        const viewport = editorViewport(root, quill, resizer, componentId);
        const scrollbars = editorScrollbars(root, quill, componentId);
        const state = {
            componentId, quill, wire, root, sync, resizer,
            get uploading() { return uploading; },
            insert(media) {
                if (! media?.url) return;
                const index = Math.min(lastRange.index, Math.max(0, quill.getLength() - 1));
                if (String(media.mime_type || '').startsWith('image/')) {
                    quill.insertEmbed(index, 'image', media.url, 'user');
                    const [leaf] = quill.getLeaf(index + 1);
                    if (leaf?.domNode?.tagName === 'IMG') leaf.domNode.setAttribute('alt', media.alt_text || media.name || '');
                    lastRange = { index: index + 1, length: 0 };
                } else {
                    const text = media.name || 'Dokumen PDF';
                    quill.insertText(index, text, { link: media.url }, 'user');
                    quill.insertText(index + text.length, ' ', { link: false }, 'user');
                    lastRange = { index: index + text.length + 1, length: 0 };
                }
                if (! pickerFor(componentId)) state.focus();
                else requestAnimationFrame(() => {
                    const picker = pickerFor(componentId);
                    if (picker && ! picker.contains(document.activeElement)) picker.querySelector('input[type="search"]')?.focus();
                });
                sync();
            },
            focus() {
                quill.setSelection(Math.min(lastRange.index, Math.max(0, quill.getLength() - 1)), 0, 'silent');
                quill.focus();
            },
            destroy() {
                scrollbars.destroy();
                viewport.destroy();
                resizer.destroy();
                quill.off('text-change', sync);
                quill.root.removeEventListener('paste', upload, true);
                quill.root.removeEventListener('drop', upload, true);
                root.removeEventListener('cms-upload-start', uploadStart);
                root.removeEventListener('cms-upload-progress', uploadProgress);
                root.removeEventListener('cms-upload-finish', uploadFinish);
                root.removeEventListener('cms-upload-error', uploadError);
                mediaButton?.removeEventListener('click', openMedia);
                root.querySelector('.ql-toolbar')?.remove();
                surface.replaceChildren();
                editors.delete(root);
            },
        };
        function openPicker(type) {
            lastRange = quill.getSelection() || lastRange;
            resizer.hide();
            root.dispatchEvent(new CustomEvent('cms-open-media-picker', { bubbles: true, detail: { componentId, type, purpose: 'editor' } }));
        }
        editors.set(root, state);
    }

    function initTree(root) {
        if (! window.Sortable) return;
        if (trees.has(root)) { trees.get(root).refresh(); return; }
        const wire = wireFor(root);
        if (! wire) return;
        const instances = new Map();
        const serialize = () => {
            const order = [];
            const visit = (list, parentKey = null) => Array.from(list.children).forEach((item) => {
                if (! item.matches('[data-cms-menu-item]')) return;
                order.push({ key: item.dataset.key, parent_key: parentKey });
                const children = Array.from(item.children).find((element) => element.matches('[data-cms-menu-list]'));
                if (children) visit(children, item.dataset.key);
            });
            visit(root.querySelector('[data-cms-menu-root]'));
            return order;
        };
        const depth = (list) => {
            let count = 1;
            let parent = list.closest('[data-cms-menu-item]');
            while (parent) { count++; parent = parent.parentElement?.closest('[data-cms-menu-item]'); }
            return count;
        };
        const subtreeDepth = (item) => {
            const list = Array.from(item.children).find((child) => child.matches('[data-cms-menu-list]'));
            const children = list ? Array.from(list.children).filter((child) => child.matches('[data-cms-menu-item]')) : [];
            return children.length ? 1 + Math.max(...children.map(subtreeDepth)) : 1;
        };
        const refresh = () => {
            for (const [list, sortable] of instances) {
                if (! root.contains(list)) { sortable.destroy(); instances.delete(list); }
            }
            root.querySelectorAll('[data-cms-menu-list]').forEach((list) => {
                if (instances.has(list)) return;
                instances.set(list, new window.Sortable(list, {
                group: `cms-menu-${owner(root).getAttribute('wire:id')}`,
                animation: 150, handle: '[data-cms-menu-handle]', draggable: '[data-cms-menu-item]',
                ghostClass: 'cms-menu-ghost', chosenClass: 'cms-menu-chosen', fallbackOnBody: true,
                swapThreshold: .65, emptyInsertThreshold: 18,
                onMove(event) { return depth(event.to) + subtreeDepth(event.dragged) - 1 <= 3 && ! event.dragged.contains(event.to); },
                async onEnd() {
                    try { await wire.reorderMenuItems(serialize()); }
                    catch { wire.$refresh(); }
                },
                }));
            });
        };
        trees.set(root, { refresh, destroy() { instances.forEach((item) => item.destroy()); trees.delete(root); } });
        refresh();
    }

    function initCategoryTable(root) {
        if (! window.Sortable || ! root) return;
        if (categoryOrders.has(root)) { categoryOrders.get(root).refresh(); return; }
        const wire = wireFor(root);
        if (! wire) return;
        let body = null, sortable = null, busy = false, drag = null;
        const marker = row => row?.querySelector('[data-cms-category-row]');
        const rows = () => body ? Array.from(body.children).filter(row => marker(row) && ! row.classList.contains('cms-category-row-fallback')) : [];
        const peers = row => rows().filter(candidate => marker(candidate).dataset.parent === marker(row).dataset.parent);
        const feedback = (message, error = false) => {
            const element = root.querySelector('[data-cms-category-feedback]');
            if (element) { element.textContent = message; element.classList.toggle('text-danger', error); }
        };
        const setBusy = value => {
            busy = value;
            root.toggleAttribute('data-cms-category-saving', value);
            sortable?.option('disabled', value);
            root.querySelectorAll('[data-cms-category-handle]').forEach(button => { button.disabled = value; });
        };
        const restoreDrag = () => {
            if (! drag) return;
            drag.hidden.forEach(row => row.hidden = false);
            // Restore the server's DOM before Livewire morphs its wire:replace rows.
            if (drag.original.every(row => row.parentElement === body)) {
                drag.original.forEach(row => body.appendChild(row));
            }
            drag = null;
            root.removeAttribute('data-cms-category-dragging');
        };
        const move = async (source, target, placement) => {
            if (busy || ! source || ! target) return;
            const id = Number(source.dataset.id);
            setBusy(true); feedback('Menyimpan urutan…');
            try {
                const saved = await wire.moveCategory(id, Number(target.dataset.id), placement, source.dataset.version);
                feedback(saved ? 'Urutan tersimpan.' : 'Urutan belum berubah. Periksa pesan di atas.', ! saved);
            } catch {
                feedback('Urutan belum tersimpan. Periksa koneksi lalu geser kembali.', true);
                try { await wire.$refresh(); } catch { /* Keep the original visible order for a retry. */ }
            } finally {
                setBusy(false); refresh();
                root.querySelector('[data-cms-category-row][data-id="' + id + '"] [data-cms-category-handle]')?.focus({ preventScroll: true });
            }
        };
        const refresh = () => {
            if (drag || busy) return;
            const first = root.querySelector('[data-cms-category-row][data-reorderable="true"]');
            const nextBody = first?.closest('tr')?.parentElement || null;
            if (body !== nextBody) { sortable?.destroy(); sortable = null; body = nextBody; }
            if (! body) return;
            rows().forEach(row => { row.toggleAttribute('data-cms-draggable-row', marker(row).dataset.reorderable === 'true'); });
            if (sortable) return;
            sortable = new window.Sortable(body, {
                animation: 150, draggable: '>tr[data-cms-draggable-row]', handle: '[data-cms-category-handle],.cms-category-name',
                forceFallback: true, fallbackOnBody: true, fallbackTolerance: 4,
                delayOnTouchOnly: true, delay: 120, swapThreshold: .65,
                ghostClass: 'cms-category-row-ghost', chosenClass: 'cms-category-row-chosen', fallbackClass: 'cms-category-row-fallback',
                onClone(event) {
                    const cells = Array.from(event.item.children);
                    Array.from(event.clone.children).forEach((cell, index) => { cell.style.width = cells[index].getBoundingClientRect().width + 'px'; });
                    event.clone.style.width = event.item.getBoundingClientRect().width + 'px';
                },
                onStart(event) {
                    const ordered = rows(), index = ordered.indexOf(event.item), depth = Number(marker(event.item).dataset.depth);
                    const hidden = [];
                    for (let i = index + 1; i < ordered.length && Number(marker(ordered[i]).dataset.depth) > depth; i++) hidden.push(ordered[i]);
                    drag = { original: Array.from(body.children).filter(row => ! row.classList.contains('cms-category-row-fallback')), hidden, peers: peers(event.item), source: marker(event.item) };
                    hidden.forEach(row => row.hidden = true);
                    root.setAttribute('data-cms-category-dragging', '');
                    feedback('Lepaskan pada kategori lain dengan induk yang sama.');
                },
                onMove(event) {
                    return marker(event.related)?.dataset.parent === marker(event.dragged)?.dataset.parent;
                },
                onEnd(event) {
                    if (! drag) return;
                    const siblings = peers(event.item), previousIndex = drag.peers.indexOf(event.item), index = siblings.indexOf(event.item);
                    const target = marker(index > previousIndex ? siblings[index - 1] : siblings[index + 1]);
                    const source = drag.source;
                    restoreDrag();
                    if (index !== previousIndex && target) move(source, target, index > previousIndex ? 'after' : 'before');
                    else feedback('');
                },
            });
        };
        const keydown = event => {
            const handle = event.target.closest('[data-cms-category-handle]');
            if (! handle || ! event.altKey || ! ['ArrowUp', 'ArrowDown'].includes(event.key) || busy) return;
            event.preventDefault();
            const row = handle.closest('tr'), siblings = peers(row), direction = event.key === 'ArrowUp' ? -1 : 1;
            const target = siblings[siblings.indexOf(row) + direction];
            if (target) move(marker(row), marker(target), direction === -1 ? 'before' : 'after');
        };
        root.addEventListener('keydown', keydown);
        categoryOrders.set(root, { refresh, destroy() {
            restoreDrag(); sortable?.destroy(); root.removeEventListener('keydown', keydown);
            root.removeAttribute('data-cms-category-saving'); categoryOrders.delete(root);
        } });
        refresh();
    }

    function initPicker(root) {
        if (pickers.has(root)) return;
        const wire = wireFor(root);
        if (! wire) return;
        const componentId = root.dataset.cmsPickerOwner || owner(root)?.getAttribute('wire:id');
        for (const state of editors.values()) if (state.componentId === componentId) state.resizer.hide();
        if (! pickers.size) {
            previousPickerOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
        }
        const focusInside = () => (root.querySelector('input[type="search"]') || root).focus();
        const focusin = (event) => { if (root.isConnected && ! root.contains(event.target)) focusInside(); };
        const keydown = (event) => {
            if (event.key === 'Escape') { event.preventDefault(); wire.closeMediaPicker(); return; }
            if (event.key !== 'Tab') return;
            const fields = Array.from(root.querySelectorAll('button,input,select,a[href],[tabindex="0"]')).filter((item) => ! item.disabled && item.getBoundingClientRect().width > 0);
            const first = fields[0];
            const last = fields[fields.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (! event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        };
        root.addEventListener('keydown', keydown);
        document.addEventListener('focusin', focusin);
        pickers.set(root, { destroy() {
            root.removeEventListener('keydown', keydown);
            document.removeEventListener('focusin', focusin);
            pickers.delete(root);
            if (! pickers.size && previousPickerOverflow !== null) {
                document.body.style.overflow = previousPickerOverflow;
                previousPickerOverflow = null;
            }
        } });
        requestAnimationFrame(() => { if (root.isConnected) focusInside(); });
    }

    function initPostDialog(root) {
        if (postDialogs.has(root)) {
            requestAnimationFrame(() => { if (root.isConnected && !root.contains(document.activeElement)) root.focus({ preventScroll: true }); });
            return;
        }
        const wire = wireFor(root);
        if (! wire) return;
        const previous = document.activeElement;
        const component = owner(root);
        const overflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const fields = () => Array.from(root.querySelectorAll('button,input,select,a[href],[tabindex="0"]')).filter(item => !item.disabled && item.getBoundingClientRect().width > 0);
        const focusInside = () => root.focus({ preventScroll: true });
        const focusin = event => { if (root.isConnected && !root.contains(event.target)) focusInside(); };
        const keydown = event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                if (root.getAttribute('aria-busy') !== 'true') wire.cancelPostAction();
                return;
            }
            if (event.key !== 'Tab') return;
            const items = fields(), first = items[0], last = items[items.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === root)) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        };
        root.addEventListener('keydown', keydown);
        document.addEventListener('focusin', focusin);
        const focusObserver = new MutationObserver(() => requestAnimationFrame(() => {
            if (root.isConnected && root.getAttribute('aria-busy') !== 'true' && !root.contains(document.activeElement)) focusInside();
        }));
        focusObserver.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'aria-busy'] });
        postDialogs.set(root, { destroy() {
            focusObserver.disconnect();
            root.removeEventListener('keydown', keydown);
            document.removeEventListener('focusin', focusin);
            document.body.style.overflow = overflow;
            postDialogs.delete(root);
            requestAnimationFrame(() => {
                if (document.querySelector('[data-cms-post-action]')) return;
                if (previous?.matches('button,a[href],input,select,textarea,[tabindex]') && previous.isConnected && previous.getBoundingClientRect().width > 0) previous.focus({ preventScroll: true });
                else component?.querySelector('[data-starter-region="bulk-actions"] button')?.focus({ preventScroll: true });
            });
        } });
        requestAnimationFrame(() => { if (root.isConnected) focusInside(); });
    }

    function initPublicationPicker(root) {
        if (! window.flatpickr) return;
        if (publicationPickers.has(root)) { publicationPickers.get(root).refresh(); return; }
        const wire = wireFor(root);
        const input = root.querySelector('[data-cms-publish-input]');
        const nowButton = root.querySelector('[data-cms-publish-now]');
        if (! wire || ! input) return;
        const currentWib = () => window.StarterCms.maskDateTime(window.StarterCms.localDateTime('Asia/Jakarta'));
        const instance = window.flatpickr(input, {
            enableTime: true, time_24hr: true, minuteIncrement: 1, disableMobile: true,
            dateFormat: 'd/m/Y H:i', ariaDateFormat: 'j F Y',
            defaultDate: wire.$get?.('publishedAtDisplay') || input.value || undefined,
            now: currentWib(),
            locale: {
                firstDayOfWeek: 1,
                weekdays: { shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'], longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] },
                months: { shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] },
                yearAriaLabel: 'Tahun', monthAriaLabel: 'Bulan', hourAriaLabel: 'Jam', minuteAriaLabel: 'Menit',
                scrollTitle: 'Gulir untuk mengubah', toggleTitle: 'Klik untuk mengganti',
            },
            onChange: (_dates, value) => wire.$set('publishedAtDisplay', value, false),
            onValueUpdate: (_dates, value) => wire.$set('publishedAtDisplay', value, false),
            onOpen: () => input.setAttribute('aria-expanded', 'true'),
            onClose: () => input.setAttribute('aria-expanded', 'false'),
            onReady: (_dates, _value, picker) => {
                const calendar = picker.calendarContainer;
                calendar.classList.add('cms-publication-calendar');
                calendar.id = `cms-publication-calendar-${owner(root).getAttribute('wire:id')}`;
                calendar.setAttribute('role', 'dialog');
                calendar.setAttribute('aria-label', 'Pilih tanggal dan jam terbit (WIB)');
                input.setAttribute('aria-controls', calendar.id);
                const footer = document.createElement('div');
                footer.className = 'cms-publication-calendar-footer';
                const zone = document.createElement('span');
                zone.textContent = 'Waktu Indonesia Barat';
                const done = document.createElement('button');
                done.type = 'button';
                done.className = 'btn btn-sm btn-primary';
                done.textContent = 'Selesai';
                done.addEventListener('click', () => { input.focus(); picker.close(); });
                footer.append(zone, done);
                calendar.append(footer);
            },
        });
        const setNow = () => instance.setDate(currentWib(), true);
        nowButton?.addEventListener('click', setNow);
        publicationPickers.set(root, {
            refresh() {
                if (instance.isOpen) return;
                const value = wire.$get?.('publishedAtDisplay') ?? input.value;
                if (value !== input.value) instance.setDate(value, false);
            },
            destroy() {
                nowButton?.removeEventListener('click', setNow);
                instance.destroy();
                publicationPickers.delete(root);
            },
        });
    }

    const init = () => {
        document.querySelectorAll('[data-cms-editor]').forEach(initEditor);
        document.querySelectorAll('[data-cms-publish-picker]').forEach(initPublicationPicker);
        document.querySelectorAll('[data-cms-menu-builder]').forEach(initTree);
        document.querySelectorAll('[data-cms-category-row]').forEach(row => initCategoryTable(owner(row)));
        document.querySelectorAll('[data-cms-picker]').forEach(initPicker);
        document.querySelectorAll('[data-cms-post-action]').forEach(initPostDialog);
        for (const [root, state] of editors) if (! root.isConnected) state.destroy();
        for (const [root, state] of trees) if (! root.isConnected) state.destroy();
        for (const [root, state] of categoryOrders) if (! root.isConnected) state.destroy();
        for (const [root, state] of pickers) if (! root.isConnected) state.destroy();
        for (const [root, state] of postDialogs) if (! root.isConnected) state.destroy();
        for (const [root, state] of publicationPickers) if (! root.isConnected) state.destroy();
    };
    window.StarterCms = {
        init,
        categoryPicker(selected) {
            return {
                selected, search: '', expanded: [], visible: [], root: null,
                nodes() {
                    return Array.from(this.root.querySelectorAll('input[data-parent]'), input => ({ id: Number(input.value), parent: Number(input.dataset.parent), name: input.dataset.name }));
                },
                init() {
                    this.root = this.$el;
                    const nodes = this.nodes(), byId = new Map(nodes.map(node => [node.id, node]));
                    for (const id of this.selected.map(Number)) {
                        let parent = byId.get(id)?.parent;
                        const seen = new Set();
                        while (parent && byId.has(parent) && !seen.has(parent)) {
                            seen.add(parent); this.expanded.push(parent); parent = byId.get(parent).parent;
                        }
                    }
                    this.refresh();
                    this.$watch('search', () => this.refresh());
                },
                toggle(id) {
                    this.expanded = this.expanded.includes(id) ? this.expanded.filter(value => value !== id) : [...this.expanded, id];
                    this.refresh();
                },
                refresh() {
                    const nodes = this.nodes(), byId = new Map(nodes.map(node => [node.id, node]));
                    const query = this.search.trim().toLocaleLowerCase('id');
                    const visible = new Set();
                    for (const node of nodes) {
                        const ancestors = [], seen = new Set([node.id]);
                        let parent = byId.get(node.parent);
                        while (parent && !seen.has(parent.id)) {
                            seen.add(parent.id); ancestors.push(parent); parent = byId.get(parent.parent);
                        }
                        if (query) {
                            if ([node, ...ancestors].some(item => item.name.toLocaleLowerCase('id').includes(query))) {
                                visible.add(node.id); for (const ancestor of ancestors) visible.add(ancestor.id);
                            }
                        } else if (ancestors.every(item => this.expanded.includes(item.id))) visible.add(node.id);
                    }
                    this.visible = Array.from(visible);
                },
                choose(root, id, checked) {
                    this.selected = window.StarterCms.categorySelection(root, this.selected, id, checked);
                },
            };
        },
        categorySelection(root, values, id, checked) {
            const nodes = new Map(Array.from(root.querySelectorAll('input[type="checkbox"][data-parent]'), input => [Number(input.value), Number(input.dataset.parent)]));
            const selected = new Set(values.map(Number));
            const branch = new Set([id]);
            // Expand the clicked branch even when descendants are hidden by search.
            for (const parent of branch) for (const [child, parentId] of nodes) if (parentId === parent) branch.add(child);
            for (const child of branch) checked ? selected.add(child) : selected.delete(child);
            const seen = new Set();
            let parent = nodes.get(id);
            while (parent && nodes.has(parent) && !seen.has(parent)) {
                seen.add(parent);
                if (checked) selected.add(parent);
                else if (!Array.from(nodes).some(([child, parentId]) => parentId === parent && selected.has(child))) selected.delete(parent);
                parent = nodes.get(parent);
            }
            return Array.from(selected);
        },
        slug(title) {
            const substitutions = { ß: 'ss', æ: 'ae', œ: 'oe', ø: 'o', đ: 'd', ð: 'd', ł: 'l', þ: 'th' };
            return String(title || '').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[ßæœøđðłþ]/g, (letter) => substitutions[letter]).replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '').slice(0, 180).replace(/-+$/g, '');
        },
        localDateTime(timeZone) {
            const now = new Date();
            if (timeZone) {
                return new Intl.DateTimeFormat('sv-SE', {
                    timeZone, year: 'numeric', month: '2-digit', day: '2-digit',
                    hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
                }).format(now).replace(' ', 'T');
            }
            const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
            return local.toISOString().slice(0, 16);
        },
        maskDateTime(value) {
            const source = String(value || '');
            const canonical = source.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
            const digits = canonical ? `${canonical[3]}${canonical[2]}${canonical[1]}${canonical[4]}${canonical[5]}` : source.replace(/\D/g, '').slice(0, 12);
            let masked = digits.slice(0, 2);
            if (digits.length > 2) masked += '/' + digits.slice(2, 4);
            if (digits.length > 4) masked += '/' + digits.slice(4, 8);
            if (digits.length > 8) masked += ' ' + digits.slice(8, 10);
            if (digits.length > 10) masked += ':' + digits.slice(10, 12);
            return masked;
        },
        dropFiles(wire, event, field = 'uploads') {
            event.preventDefault();
            const root = event.currentTarget.closest('[data-cms-dropzone]') || event.currentTarget;
            uploadFiles(wire, event.dataTransfer?.files, field, root);
        },
    };
    document.addEventListener('submit', (event) => {
        for (const state of editors.values()) {
            if (state.root.closest('form') !== event.target) continue;
            if (state.uploading) { event.preventDefault(); event.stopImmediatePropagation(); return; }
            state.sync();
        }
    }, true);
    document.addEventListener('cms-editor-insert', (event) => {
        for (const state of editors.values()) if (state.componentId === event.detail.componentId) state.insert(event.detail.media);
    });
    document.addEventListener('cms-editor-picker-closed', (event) => {
        if (event.detail.purpose && event.detail.purpose !== 'editor') return;
        requestAnimationFrame(() => {
            for (const state of editors.values()) if (state.root.isConnected && state.componentId === event.detail.componentId && ! pickerFor(state.componentId)) state.focus();
        });
    });
    document.addEventListener('cms-menu-reset', () => {
        for (const state of trees.values()) state.destroy();
        for (const state of pickers.values()) state.destroy();
        requestAnimationFrame(init);
    });
    document.addEventListener('load', (event) => { if (event.target.tagName === 'SCRIPT') init(); }, true);
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('livewire:initialized', init);
    document.addEventListener('livewire:navigated', init);
    document.addEventListener('livewire:navigating', () => {
        for (const state of postDialogs.values()) state.destroy();
        for (const state of categoryOrders.values()) state.destroy();
        for (const state of publicationPickers.values()) state.destroy();
        for (const state of pickers.values()) state.destroy();
        for (const state of editors.values()) state.destroy();
        for (const state of trees.values()) state.destroy();
    });
    observer = new MutationObserver((mutations) => {
        const meaningful = mutations.some((mutation) => Array.from(mutation.addedNodes).some((node) => node.nodeType === 1 && (node.matches('[data-cms-editor],[data-cms-menu-builder],[data-cms-menu-list],[data-cms-picker],[data-cms-publish-picker],[data-cms-category-row],[data-cms-post-action]') || node.querySelector('[data-cms-editor],[data-cms-menu-builder],[data-cms-menu-list],[data-cms-picker],[data-cms-publish-picker],[data-cms-category-row],[data-cms-post-action]'))));
        if (meaningful) init();
        for (const [root, state] of categoryOrders) if (! root.isConnected) state.destroy();
        for (const [root, state] of editors) if (! root.isConnected) state.destroy();
        for (const [root, state] of trees) if (! root.isConnected) state.destroy();
        for (const [root, state] of pickers) if (! root.isConnected) state.destroy();
        for (const [root, state] of postDialogs) if (! root.isConnected) state.destroy();
        for (const [root, state] of publicationPickers) if (! root.isConnected) state.destroy();
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
    init();
})();
