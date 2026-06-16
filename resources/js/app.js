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
