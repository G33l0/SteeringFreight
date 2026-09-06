@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (\App\Services\MediaService::url(setting('company.logo')))
<img src="{{ \App\Services\MediaService::url(setting('company.logo')) }}" class="logo" alt="{{ company_name() }}">
@else
<img src="{{ asset('assets/brand/portlane-logo-horizontal.png') }}" class="logo" alt="{{ company_name() }}">
@endif
</a>
</td>
</tr>
