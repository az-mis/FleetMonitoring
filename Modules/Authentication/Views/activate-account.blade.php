<x-public-shell title="Activate your account" active="login" width="max-w-md"
    kicker="Account Activation" heading="Set up your driver account"
    lede="Create your own password to start using the Fleet Management System."
    :points="[
        [1, 'Create a password', 'At least 12 characters, with letters and numbers.'],
        [2, 'You are signed in', 'Your dashboard opens right away.'],
        [3, 'Next time', 'Sign in with this email address and your new password.'],
    ]">

    @if ($usable)
        <div class="mb-5">
            <h1 class="text-[1.65rem] font-bold tracking-tight text-pub-text">Create Your Password</h1>
            <p class="mt-1.5 text-sm leading-6 text-pub-muted">Hello, <span class="font-semibold text-pub-text">{{ $user->name }}</span>. Choose the password you will use to sign in.</p>
        </div>

        <form method="POST" action="{{ request()->getRequestUri() }}" class="space-y-4" onsubmit="this.querySelector('[type=submit]').disabled = true">
            @csrf

            <div>
                <label for="email" class="block text-[0.8125rem] font-semibold text-pub-body">Email address</label>
                <input id="email" type="email" value="{{ $user->email }}" readonly autocomplete="username"
                    class="mt-1.5 block h-11 w-full rounded-lg border border-pub-line bg-pub-subtle px-3 py-2.5 text-sm text-pub-muted">
            </div>

            <div>
                <label for="password" class="block text-[0.8125rem] font-semibold text-pub-body">New password</label>
                <div class="relative mt-1.5">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        class="block h-11 w-full rounded-lg border border-pub-border bg-pub-field shadow-xs py-2.5 pl-3 pr-10 text-sm text-pub-text transition focus:border-pub-accent focus:outline-none focus:ring-4 focus:ring-pub-accent/15"
                    >
                    @include('authentication::partials.password-eye', ['for' => 'password'])
                </div>
                @error('password')
                    <p id="password-error" class="mt-1.5 text-xs font-semibold text-pub-danger" role="alert">{{ $message }}</p>
                @else
                    <p class="mt-1.5 text-xs text-pub-faint">At least 12 characters, with letters and numbers.</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-[0.8125rem] font-semibold text-pub-body">Confirm new password</label>
                <div class="relative mt-1.5">
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="block h-11 w-full rounded-lg border border-pub-border bg-pub-field shadow-xs py-2.5 pl-3 pr-10 text-sm text-pub-text transition focus:border-pub-accent focus:outline-none focus:ring-4 focus:ring-pub-accent/15"
                    >
                    @include('authentication::partials.password-eye', ['for' => 'password_confirmation'])
                </div>
            </div>

            <button
                type="submit"
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-pub-primary px-4 text-sm font-semibold text-pub-on-primary shadow-xs hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-4 focus-visible:ring-pub-accent/25 disabled:cursor-not-allowed disabled:opacity-60 transition"
            >
                Activate and Sign In
            </button>
        </form>
    @else
        <div class="mb-5">
            <h1 class="text-[1.65rem] font-bold tracking-tight text-pub-text">This Link No Longer Works</h1>
            <p class="mt-1.5 text-sm leading-6 text-pub-muted">Activation links work for {{ \Modules\Authentication\Support\AccountActivation::EXPIRES_DAYS }} days and only once. It may have expired, been used already, or been replaced by a newer email address.</p>
        </div>
        <div class="rounded-lg border border-pub-warning-border bg-pub-warning-soft px-4 py-3 text-sm leading-6 text-pub-body" role="status">
            Ask the fleet administrator at your station to send you a new activation link. If you already created your password, just sign in.
        </div>
        <a href="{{ route('login') }}" class="mt-5 inline-flex h-11 w-full items-center justify-center rounded-lg bg-pub-primary px-4 text-sm font-semibold text-pub-on-primary shadow-xs hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-4 focus-visible:ring-pub-accent/25 transition">
            Go to Sign In
        </a>
    @endif
</x-public-shell>
