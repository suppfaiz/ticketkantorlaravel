<a
    href="{{ route('tickets.create') }}"
    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group relative {{ request()->routeIs('tickets.create') ? 'bg-white/20 text-white font-bold shadow-xs backdrop-blur-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
    :class="{
        'lg:justify-center lg:px-0': $store.sidebar.collapsed,
        'gap-3 px-3': !$store.sidebar.collapsed
    }"
    :title="$store.sidebar.collapsed ? 'Buat Tiket Baru' : ''"
>
    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all {{ request()->routeIs('tickets.create') ? 'bg-[#11CDEF] text-white shadow-xs' : 'bg-white/15 text-cyan-300 group-hover:bg-[#11CDEF] group-hover:text-white' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
    </div>
    <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
        Buat Tiket Baru
    </span>
</a>
