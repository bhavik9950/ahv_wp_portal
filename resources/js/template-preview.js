/**
 * Alpine component for the "Send test message" template picker.
 *
 * Reads the template structure blob rendered server-side into
 * <script type="application/json" id="test-send-templates"> and, as the user
 * types, shows a WhatsApp-style preview of the final message.
 *
 * All substitution is done with plain text nodes (x-text / :value) — no HTML is
 * ever built from user input.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('testSendTemplate', () => ({
        templates: {},
        templateId: '',
        values: {}, // { [varIndex]: string }

        init() {
            const el = document.getElementById('test-send-templates');
            try {
                this.templates = JSON.parse(el?.textContent || '{}');
            } catch (e) {
                this.templates = {};
            }

            // Restore the previously chosen template + values after a validation bounce.
            const initial = el?.dataset.selected;
            let old = [];
            try {
                old = JSON.parse(el?.dataset.oldValues || '[]');
            } catch (e) {
                old = [];
            }
            (Array.isArray(old) ? old : []).forEach((val, i) => {
                this.values[i + 1] = val ?? '';
            });

            if (initial && this.templates[initial]) {
                this.templateId = initial;
            }
            this.syncValues();
        },

        varLabel(n) {
            return `{{${n}}}`;
        },

        get tpl() {
            return this.templates[this.templateId] || null;
        },

        get mediaHeader() {
            return this.tpl?.header && this.tpl.header.format !== 'TEXT'
                ? this.tpl.header.format
                : null;
        },

        onSelect() {
            this.syncValues();
        },

        syncValues() {
            const next = {};
            (this.tpl?.variables || []).forEach((v) => {
                next[v.index] = this.values[v.index] ?? '';
            });
            this.values = next;
        },

        /** A body/header string with {{n}} replaced by the typed value (or a hint). */
        render(text) {
            if (!text) return '';
            return text.replace(/\{\{\s*(\d+)\s*\}\}/g, (match, n) => {
                const typed = (this.values[n] ?? '').trim();
                if (typed) return typed;
                const example = (this.tpl?.variables || []).find((v) => String(v.index) === n)?.example;
                return example ? example : match;
            });
        },

        /** Split a string into plain / {{n}} chunks so the structure view can chip them. */
        chunks(text) {
            if (!text) return [];
            return text
                .split(/(\{\{\s*\d+\s*\}\})/)
                .filter((part) => part !== '')
                .map((part) => ({
                    text: part,
                    isVar: /^\{\{\s*\d+\s*\}\}$/.test(part),
                }));
        },
    }));
});
