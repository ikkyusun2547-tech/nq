/**
 * App-wide "something is happening" feedback, so a slow phone connection
 * never looks frozen and nobody double-submits:
 *
 * 1. A thin purple progress bar at the top of the screen while the next page
 *    loads (link clicks, form submits, back/forward).
 * 2. On POST/PUT/PATCH/DELETE forms, the button that was pressed turns into
 *    a spinner + "กำลังส่ง…" and the form ignores further submits.
 *
 * Opt out per element with data-no-loading (on the <a>, <form> or button).
 * Forms that handle their own submit (preventDefault + fetch, e.g. the
 * check-in page) are skipped automatically.
 */

const SAFETY_RESET_MS = 12000; // e.g. a POST that answers with a file download keeps us on the page

// --- 1. Top progress bar ---------------------------------------------------

let bar = null;
let trickle = null;
let safety = null;

function ensureBar() {
    if (bar) return bar;
    bar = document.createElement('div');
    bar.className = 'srru-progress';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);
    return bar;
}

function startProgress() {
    const el = ensureBar();
    clearInterval(trickle);
    clearTimeout(safety);
    let width = 8;
    el.style.transition = 'none';
    el.style.width = '0%';
    el.style.opacity = '1';
    // Next frame: animate from 0 so a second navigation restarts visibly.
    requestAnimationFrame(() => {
        el.style.transition = 'width 0.3s ease, opacity 0.3s ease';
        el.style.width = width + '%';
    });
    // Creep towards 90% — it never "finishes" until the new page replaces this one.
    trickle = setInterval(() => {
        width += (90 - width) * 0.08;
        el.style.width = width + '%';
    }, 250);
    safety = setTimeout(stopProgress, SAFETY_RESET_MS);
}

function stopProgress() {
    clearInterval(trickle);
    clearTimeout(safety);
    if (! bar) return;
    bar.style.width = '100%';
    setTimeout(() => {
        bar.style.opacity = '0';
        setTimeout(() => { bar.style.width = '0%'; }, 300);
    }, 150);
}

function isNavigatingLink(event, link) {
    if (event.defaultPrevented || event.button !== 0) return false;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.hasAttribute('download') || link.closest('[data-no-loading]')) return false;
    if (link.target && link.target !== '_self') return false;
    const href = link.getAttribute('href');
    if (! href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin) return false;
    // Same page, only the hash changes: no load happens.
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return false;
    return true;
}

// Bubble phase on document, so any handler on the link itself has already had
// its chance to preventDefault (e.g. Alpine @click.prevent).
document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');
    if (link && isNavigatingLink(event, link)) startProgress();
});

// --- 2. Busy submit buttons --------------------------------------------------

const BUSY_LABEL = document.documentElement.lang?.startsWith('en') ? 'Sending…' : 'กำลังส่ง…';
const busyButtons = new Set();

function markBusy(form, submitter) {
    const method = (form.getAttribute('method') || 'get').toLowerCase();
    if (method === 'get') return; // filters / search: the top bar is enough
    if (form.closest('[data-no-loading]') || submitter?.closest('[data-no-loading]')) return;

    form.dataset.submitting = '1';

    const button = submitter && submitter.tagName === 'BUTTON' ? submitter
        : form.querySelector('button[type=submit], button:not([type])');
    if (! button || button.dataset.busy) return;

    button.dataset.busy = '1';
    button.dataset.originalHtml = button.innerHTML;
    button.style.minWidth = button.offsetWidth + 'px'; // keep the button from jumping in size
    button.setAttribute('aria-busy', 'true');
    button.classList.add('srru-busy');
    button.innerHTML = `<span class="srru-spinner" aria-hidden="true"></span><span>${BUSY_LABEL}</span>`;
    busyButtons.add(button);
    // Disable only after the browser has read the form, so a named submit
    // button still sends its name/value.
    setTimeout(() => { button.disabled = true; }, 0);
}

function resetBusy() {
    busyButtons.forEach((button) => {
        button.innerHTML = button.dataset.originalHtml ?? button.innerHTML;
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.classList.remove('srru-busy');
        button.style.minWidth = '';
        delete button.dataset.busy;
        delete button.dataset.originalHtml;
        button.closest('form')?.removeAttribute('data-submitting');
    });
    busyButtons.clear();
    document.querySelectorAll('form[data-submitting]').forEach((form) => form.removeAttribute('data-submitting'));
}

// Capture phase: stop a second submit of a form that is already on its way
// before anyone else reacts to it.
document.addEventListener('submit', (event) => {
    if (event.target.dataset?.submitting) event.preventDefault();
}, true);

// Bubble phase: by now the form's own handlers have run; if none of them took
// over the submit, the browser is about to navigate.
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    const form = event.target;
    if (form.target && form.target !== '_self') return;
    startProgress();
    markBusy(form, event.submitter);
    setTimeout(resetBusy, SAFETY_RESET_MS);
});

// form.submit() skips the submit event entirely (a few pages call it after
// their own confirm step), so cover it too.
const nativeSubmit = HTMLFormElement.prototype.submit;
HTMLFormElement.prototype.submit = function () {
    if (! (this.target && this.target !== '_self')) {
        startProgress();
        markBusy(this, null);
        setTimeout(resetBusy, SAFETY_RESET_MS);
    }
    return nativeSubmit.call(this);
};

// Coming back via the back button restores the old page from cache, frozen
// mid-"loading" — reset everything.
window.addEventListener('pageshow', () => {
    stopProgress();
    resetBusy();
});
