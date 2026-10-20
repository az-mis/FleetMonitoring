@props(['url'])
{{-- DA MIMAROPA logo and system name above every email --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim(config('app.url'), '/') }}/da-logo.png" alt="Department of Agriculture MIMAROPA" width="56" height="56" style="display: block; margin: 0 auto 8px; height: 56px; width: 56px;">
{!! $slot !!}
</a>
</td>
</tr>
