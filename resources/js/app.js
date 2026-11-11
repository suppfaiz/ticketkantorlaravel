import './bootstrap';

import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';
import $ from 'jquery';
import 'trumbowyg/dist/ui/trumbowyg.min.css';

// Ensure jQuery is available globally for plugins
window.$ = window.jQuery = $;
globalThis.$ = globalThis.jQuery = $;

window.initTrumbowyg = async function(selector, options = {}) {
    window.$ = window.jQuery = $;
    globalThis.$ = globalThis.jQuery = $;
    await import('trumbowyg');
    if (window.$ && window.$.fn && window.$.fn.trumbowyg) {
        const baseUrl = document.querySelector('meta[name="app-base-url"]')?.content || '';
        const iconsPath = `${baseUrl}/vendor/trumbowyg/ui/icons.svg`;
        window.$.trumbowyg.svgPath = iconsPath;

        // Patch Trumbowyg prototype once for jQuery 4 compatibility & reliable Link/Format commands
        const Proto = window.$.fn.trumbowyg && window.$.trumbowyg ? null : null;
        const $el = window.$(selector);
        if ($el.length && !$el.data('trumbowyg')) {
            $el.trumbowyg({
                svgPath: iconsPath,
                semantic: false,
                resetCss: false,
                urlProtocol: true,
                autogrow: true,
                btns: [
                    ['viewHTML'],
                    ['undo', 'redo'],
                    ['formatting'],
                    ['bold', 'italic', 'underline', 'strikethrough'],
                    ['link'],
                    ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                    ['unorderedList', 'orderedList'],
                    ['horizontalRule'],
                    ['removeformat'],
                    ['fullscreen']
                ],
                ...options
            });

            const instance = $el.data('trumbowyg');
            if (instance) {
                // 1. Remove the default 'keyup focus' handler that overwrites headings/lists with <p> on empty editor
                instance.$ed.off('keyup focus');

                // 2. Save selection whenever user interacts with editor so dropdowns/modals never lose cursor position
                instance.$ed.on('mouseup keyup focus', () => {
                    instance.saveRange();
                });

                // 3. Patch createLink so it works even if clicked when editor had no active selection
                const origCreateLink = instance.createLink.bind(instance);
                instance.createLink = function() {
                    this.$ed.focus();
                    const sel = this.doc.getSelection();
                    if (!sel || sel.rangeCount === 0 || !this.$ed[0].contains(sel.focusNode)) {
                        if (this.range && this.$ed[0].contains(this.range.commonAncestorContainer)) {
                            this.restoreRange();
                        } else {
                            const range = this.doc.createRange();
                            range.selectNodeContents(this.$ed[0]);
                            range.collapse(false);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                    }
                    this.saveRange();

                    let node = sel.focusNode;
                    let text = '';
                    let url = '';
                    let title = '';
                    let target = '';
                    const linkDefaultTarget = this.o.linkTargets[0];

                    if (sel.rangeCount > 0) {
                        const selectedRange = sel.getRangeAt(0);
                        text = new XMLSerializer().serializeToString(selectedRange.cloneContents()) || selectedRange + '';
                    }

                    while (node && node !== this.$ed[0] && ['A', 'DIV'].indexOf(node.nodeName) < 0) {
                        node = node.parentNode;
                    }

                    if (node && node.nodeName === 'A') {
                        const $a = window.$(node);
                        text = $a.text();
                        url = $a.attr('href') || '';
                        if (!this.o.minimalLinks) {
                            title = $a.attr('title') || '';
                            target = $a.attr('target') || linkDefaultTarget;
                        }
                        const range = this.doc.createRange();
                        range.selectNode(node);
                        sel.removeAllRanges();
                        sel.addRange(range);
                        this.saveRange();
                    }

                    const modalOptions = {
                        url: {
                            label: this.lang.linkUrl || 'URL',
                            required: true,
                            value: url
                        },
                        text: {
                            label: this.lang.text,
                            value: text
                        }
                    };

                    if (!this.o.minimalLinks) {
                        const targetOptions = this.o.linkTargets.reduce((acc, optionValue) => {
                            acc[optionValue] = this.lang[optionValue];
                            return acc;
                        }, {});
                        window.$.extend(modalOptions, {
                            title: {
                                label: this.lang.title,
                                value: title
                            },
                            target: {
                                label: this.lang.target,
                                value: target,
                                options: targetOptions
                            }
                        });
                    }

                    this.openModalInsert(this.lang.createLink, modalOptions, (v) => {
                        const finalUrl = this.prependUrlPrefix(v.url);
                        if (!finalUrl || !finalUrl.length) {
                            return false;
                        }
                        const link = window.$(['<a href="', finalUrl, '">', v.text || v.url, '</a>'].join(''));
                        if (v.title) {
                            link.attr('title', v.title);
                        }
                        if (v.target || linkDefaultTarget) {
                            link.attr('target', v.target || linkDefaultTarget);
                        }
                        this.restoreRange();
                        if (this.range) {
                            this.range.deleteContents();
                            this.range.insertNode(link[0]);
                        } else {
                            this.$ed.append(link);
                        }
                        this.syncCode();
                        this.$c.trigger('tbwchange');
                        return true;
                    });
                };

                // 4. Patch closeModal for jQuery 4 compatibility (unitless top string)
                instance.closeModal = function() {
                    const prefix = this.o.prefix;
                    if (!this.$btnPane.hasClass(prefix + 'disable')) {
                        return;
                    }
                    this.$btnPane.removeClass(prefix + 'disable');
                    this.$overlay.off();
                    const $modalBox = window.$('.' + prefix + 'modal-box', window.$(this.doc.body));
                    $modalBox.parent().remove();
                    this.hideOverlay();
                    this.$c.trigger('tbwmodalclose');
                    this.restoreRange();
                };

                // 5. Keep underlying textarea synced at all times
                const syncToTextarea = () => {
                    if (instance.$box.hasClass('trumbowyg-editor-hidden')) {
                        instance.$ed.html(instance.$ta.val());
                    } else {
                        const rawText = instance.$ed.text().trim();
                        const hasKeepTags = instance.$ed.find('hr,img,embed,iframe,input,ul,ol,blockquote,h1,h2,h3,h4,a').length > 0;
                        instance.$ta.val(rawText.length > 0 || hasKeepTags ? instance.$ed.html() : '');
                    }
                };

                instance.$ed.on('input keyup blur tbwchange tbwpaste', syncToTextarea);
                instance.$ta.on('input keyup blur', syncToTextarea);
                $el.on('tbwchange tbwblur', syncToTextarea);
            }
        }
    }
};

Chart.register(...registerables);
window.Chart = Chart;

// Alpine Store for global sidebar toggle across all components
Alpine.store('sidebar', {
    collapsed: localStorage.getItem('it_helpdesk_sidebar_collapsed') === 'true',
    mobileOpen: false,
    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('it_helpdesk_sidebar_collapsed', this.collapsed ? 'true' : 'false');
    },
    expand() {
        this.collapsed = false;
        localStorage.setItem('it_helpdesk_sidebar_collapsed', 'false');
    },
    collapse() {
        this.collapsed = true;
        localStorage.setItem('it_helpdesk_sidebar_collapsed', 'true');
    },
    toggleMobile() {
        this.mobileOpen = !this.mobileOpen;
    },
    closeMobile() {
        this.mobileOpen = false;
    }
});

window.Alpine = Alpine;

Alpine.start();

// Interactive Resizable Table Columns (Drag from any row cell or header boundary)
function initResizableTables() {
    document.querySelectorAll('table').forEach((table) => {
        if (table.dataset.resizableInit === 'true') return;
        const headers = Array.from(table.querySelectorAll('thead th'));
        if (headers.length < 2) return;

        table.dataset.resizableInit = 'true';

        const attachResizeBehavior = (cell, targetTh, index) => {
            const handle = document.createElement('span');
            handle.className = 'col-resize-handle';
            handle.dataset.colIndex = String(index);
            cell.appendChild(handle);

            let startX = 0;
            let startWidth = 0;

            const onMouseMove = (e) => {
                const delta = e.pageX - startX;
                const newWidth = Math.max(60, startWidth + delta);
                targetTh.style.width = `${newWidth}px`;
                targetTh.style.minWidth = `${newWidth}px`;
            };

            const onMouseUp = () => {
                table.querySelectorAll(`.col-resize-handle[data-col-index="${index}"]`).forEach((h) => {
                    h.classList.remove('is-resizing');
                });
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();

                headers.forEach((headerCell) => {
                    if (!headerCell.style.width) {
                        const w = headerCell.getBoundingClientRect().width;
                        headerCell.style.width = `${w}px`;
                    }
                });

                startX = e.pageX;
                startWidth = targetTh.getBoundingClientRect().width;
                table.querySelectorAll(`.col-resize-handle[data-col-index="${index}"]`).forEach((h) => {
                    h.classList.add('is-resizing');
                });
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        };

        headers.forEach((th, index) => {
            if (index === headers.length - 1) return;
            attachResizeBehavior(th, th, index);
        });

        table.querySelectorAll('tbody tr').forEach((tr) => {
            const cells = Array.from(tr.querySelectorAll('td'));
            cells.forEach((td, index) => {
                if (index < headers.length - 1 && headers[index]) {
                    attachResizeBehavior(td, headers[index], index);
                }
            });
        });
    });
}

/**
 * Smart AJAX Polling (Every 5 Seconds)
 * Active ONLY when the browser tab is open/visible (`document.visibilityState === 'visible'`).
 * Automatically pauses when the tab is hidden/inactive and resumes when visible again.
 */
function initSmartTabAjaxPolling() {
    if (!document.querySelector('[data-ajax-poll]')) return;

    const POLL_INTERVAL_MS = 5000;
    let pollTimer = null;
    let isFetching = false;

    const isTabOpen = () => document.visibilityState === 'visible';

    const isUserInteractingWithForm = (container) => {
        const active = document.activeElement;
        if (!active) return false;
        const tag = active.tagName;
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || active.isContentEditable) {
            return !container || container.contains(active);
        }
        return false;
    };

    const pollLiveRegions = async () => {
        if (!isTabOpen() || isFetching) return;

        const regions = document.querySelectorAll('[data-ajax-poll]');
        if (!regions.length) return;

        isFetching = true;
        try {
            const response = await fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Live-Poll': '1',
                    'Accept': 'text/html'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) return;

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            regions.forEach((currentEl) => {
                const key = currentEl.getAttribute('data-ajax-poll');
                if (!key || isUserInteractingWithForm(currentEl)) return;

                // Only update hidden tab panels if they are currently visible or notification badges
                const isHiddenTab = currentEl.style && currentEl.style.display === 'none' && !key.startsWith('topbar-');
                if (isHiddenTab) return;

                const nextEl = doc.querySelector(`[data-ajax-poll="${key}"]`);
                if (nextEl && currentEl.innerHTML !== nextEl.innerHTML) {
                    currentEl.innerHTML = nextEl.innerHTML;
                }
            });

            initResizableTables();
        } catch (err) {
            // Ignore transient network errors during background poll
        } finally {
            isFetching = false;
        }
    };

    const startPolling = () => {
        if (pollTimer || !isTabOpen()) return;
        pollTimer = setInterval(() => {
            if (isTabOpen()) {
                pollLiveRegions();
            } else {
                stopPolling();
            }
        }, POLL_INTERVAL_MS);
    };

    const stopPolling = () => {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    };

    // Event Listener: Page Visibility API (Active ONLY when tab is open)
    document.addEventListener('visibilitychange', () => {
        if (isTabOpen()) {
            pollLiveRegions();
            startPolling();
        } else {
            stopPolling();
        }
    });

    window.addEventListener('focus', () => {
        if (isTabOpen()) {
            startPolling();
        }
    });

    window.addEventListener(' pagehide', stopPolling);

    if (isTabOpen()) {
        startPolling();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initResizableTables();
        initSmartTabAjaxPolling();
    });
} else {
    initResizableTables();
    initSmartTabAjaxPolling();
}
