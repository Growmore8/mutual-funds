import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/* Global show/hide toggle on every password field (skips Alpine-bound inputs). */
function enhancePasswordFields(root = document) {
    root.querySelectorAll('input[type="password"]').forEach((inp) => {
        if (inp.dataset.pwEnhanced || inp.hasAttribute(':type') || inp.hasAttribute('x-bind:type')) return;
        inp.dataset.pwEnhanced = '1';

        const wrap = document.createElement('div');
        wrap.style.position = 'relative';
        inp.parentNode.insertBefore(wrap, inp);
        wrap.appendChild(inp);
        inp.style.paddingRight = '2.5rem';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.tabIndex = -1;
        btn.setAttribute('aria-label', 'Show password');
        btn.style.cssText = 'position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;color:#9ca3af;padding:0;line-height:1';
        btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
        btn.addEventListener('click', () => {
            const reveal = inp.type === 'password';
            inp.type = reveal ? 'text' : 'password';
            btn.innerHTML = reveal ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
        });
        wrap.appendChild(btn);
    });
}

document.addEventListener('DOMContentLoaded', () => enhancePasswordFields());
// Re-scan when modals/dynamic content open.
document.addEventListener('click', () => setTimeout(enhancePasswordFields, 50));

/* ---- Web push notifications ---- */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

async function subscribePush() {
    try {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return false;
        const meta = document.querySelector('meta[name="vapid-key"]');
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (!meta || !meta.content || !csrf) return false;

        const reg = await navigator.serviceWorker.ready;
        let sub = await reg.pushManager.getSubscription();
        if (!sub) {
            sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(meta.content),
            });
        }
        await fetch('/push/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf.content, 'Accept': 'application/json' },
            body: JSON.stringify(sub),
        });
        return true;
    } catch (e) { return false; }
}

// Triggered by an "Enable notifications" button (the permission prompt needs a user gesture).
window.enablePush = async function () {
    if (!('Notification' in window)) return 'unsupported';
    const perm = await Notification.requestPermission();
    if (perm === 'granted') await subscribePush();
    return perm;
};

// If permission is already granted, keep the subscription fresh on each load.
if ('Notification' in window && Notification.permission === 'granted') {
    window.addEventListener('load', () => subscribePush());
}
