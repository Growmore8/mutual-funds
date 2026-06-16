@props(['sound' => false])

<div class="relative" x-data="notifBell({{ $sound ? 'true' : 'false' }})" x-init="init()">
    <button @click="open = !open; if (open) markRead()" class="relative w-9 h-9 rounded-full grid place-items-center text-gray-500 hover:bg-gray-100" aria-label="Notifications">
        <i class="fa-solid fa-bell"></i>
        <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread"
              class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] grid place-items-center font-bold" style="display:none"></span>
    </button>

    <div x-show="open" @click.outside="open = false" x-transition style="display:none"
         class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
        <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
            <span class="font-semibold text-sm text-gray-900">Notifications</span>
            <button @click="markRead()" class="text-xs text-emerald-600 hover:underline">Mark all read</button>
        </div>
        <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
            <template x-for="n in items" :key="n.id">
                <a :href="n.url || '#'" class="flex gap-3 px-4 py-3 hover:bg-gray-50" :class="!n.read ? 'bg-emerald-50/40' : ''">
                    <span class="w-8 h-8 rounded-full grid place-items-center shrink-0 text-sm"
                          :class="{'bg-emerald-100 text-emerald-600': n.type==='deposit'||n.type==='profit', 'bg-amber-100 text-amber-600': n.type==='withdrawal', 'bg-blue-100 text-blue-600': n.type==='kyc'||n.type==='message', 'bg-gray-100 text-gray-500': true}">
                        <i class="fa-solid" :class="n.icon"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900" x-text="n.title"></p>
                        <p class="text-xs text-gray-500" x-text="n.body"></p>
                        <p class="text-[11px] text-gray-400 mt-0.5" x-text="n.ago"></p>
                    </div>
                </a>
            </template>
            <div x-show="items.length === 0" class="px-4 py-10 text-center text-sm text-gray-400" style="display:none">
                <i class="fa-regular fa-bell-slash text-2xl mb-2 block"></i> No notifications yet
            </div>
        </div>
    </div>
</div>

<script>
    function notifBell(sound) {
        return {
            open: false, unread: 0, items: [], sound: sound, last: -1,
            init() { this.load(); setInterval(() => this.load(), 12000); },
            async load() {
                try {
                    const r = await fetch('{{ route('notifications.feed') }}', {headers: {'Accept': 'application/json'}});
                    if (!r.ok) return;
                    const d = await r.json();
                    if (this.sound && this.last >= 0 && d.unread > this.last) this.beep();
                    this.last = d.unread;
                    this.unread = d.unread;
                    this.items = d.items;
                } catch (e) {}
            },
            async markRead() {
                try {
                    await fetch('{{ route('notifications.read') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}});
                    this.unread = 0; this.last = 0;
                    this.items = this.items.map(n => ({...n, read: true}));
                } catch (e) {}
            },
            beep() {
                try {
                    const c = new (window.AudioContext || window.webkitAudioContext)();
                    const o = c.createOscillator(), g = c.createGain();
                    o.connect(g); g.connect(c.destination);
                    o.type = 'sine'; o.frequency.value = 880;
                    g.gain.setValueAtTime(0.0001, c.currentTime);
                    g.gain.exponentialRampToValueAtTime(0.25, c.currentTime + 0.01);
                    g.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + 0.45);
                    o.start(); o.stop(c.currentTime + 0.45);
                } catch (e) {}
            },
        };
    }
</script>
