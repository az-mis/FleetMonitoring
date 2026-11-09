{{-- Show/hide button inside a password field (same icons as the sign-in page); the page's script toggles [data-toggle-password] --}}
<button type="button" data-toggle-password="{{ $for }}" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-pub-muted transition hover:text-pub-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent" aria-label="Show password">
    <svg data-eye="closed" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.1A10.8 10.8 0 0 1 12 4.8c5.2 0 8.7 4.8 9.7 7.2a13.8 13.8 0 0 1-3.1 4.2"></path><path d="M6.2 6.2A14 14 0 0 0 2.3 12c1 2.4 4.5 7.2 9.7 7.2 1 0 2-.2 2.9-.5"></path>
    </svg>
    <svg data-eye="open" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M2.3 12S5.8 4.8 12 4.8 21.7 12 21.7 12 18.2 19.2 12 19.2 2.3 12 2.3 12Z"></path><circle cx="12" cy="12" r="2.8"></circle>
    </svg>
</button>
