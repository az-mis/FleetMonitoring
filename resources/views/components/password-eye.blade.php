{{--
    Show/hide button inside a password field. Put the input and this button in a "relative" wrapper and give the input pr-10.
    tone="public" matches the sign-in pages; the default matches the forms inside the app.
--}}
@props(['for', 'tone' => 'app'])

<button type="button" data-toggle-password="{{ $for }}" aria-label="Show password" aria-controls="{{ $for }}"
    {{ $attributes->class([
        'absolute right-1.5 top-1/2 -translate-y-1/2 cursor-pointer rounded-lg p-2 transition focus:outline-none focus-visible:ring-2',
        'text-pub-muted hover:text-pub-accent focus-visible:ring-pub-accent' => $tone === 'public',
        'text-slate-400 hover:text-emerald-700 focus-visible:ring-emerald-500' => $tone !== 'public',
    ]) }}>
    <svg data-eye="closed" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.1A10.8 10.8 0 0 1 12 4.8c5.2 0 8.7 4.8 9.7 7.2a13.8 13.8 0 0 1-3.1 4.2"></path><path d="M6.2 6.2A14 14 0 0 0 2.3 12c1 2.4 4.5 7.2 9.7 7.2 1 0 2-.2 2.9-.5"></path>
    </svg>
    <svg data-eye="open" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M2.3 12S5.8 4.8 12 4.8 21.7 12 21.7 12 18.2 19.2 12 19.2 2.3 12 2.3 12Z"></path><circle cx="12" cy="12" r="2.8"></circle>
    </svg>
</button>
@once
    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-toggle-password]');
            const input = button && document.getElementById(button.dataset.togglePassword);
            if (!input) return;
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            // Slashed eye while the password is hidden, open eye while it shows
            button.querySelector('[data-eye="closed"]').classList.toggle('hidden', hidden);
            button.querySelector('[data-eye="open"]').classList.toggle('hidden', !hidden);
            button.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });
    </script>
@endonce
