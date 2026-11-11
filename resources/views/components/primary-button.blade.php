<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#1E40AF] border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#1D4ED8] focus:bg-[#1D4ED8] active:bg-[#1E3A8A] focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30 focus:ring-offset-1 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
