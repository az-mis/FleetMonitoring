<x-public-shell :title="View::yieldContent('title', config('app.name', 'Fleet Monitoring'))" active="login" width="max-w-md"
    kicker="Staff Login" heading="Sign in to manage the fleet"
    lede="For administrators and drivers of DA MIMAROPA."
    :points="[
        ['check', 'Administrators', 'Review requests, assign drivers and keep vehicles in shape.'],
        ['car', 'Drivers', 'See your assigned trips and log each one.'],
    ]">
    @yield('content')
</x-public-shell>
