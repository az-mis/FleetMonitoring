@extends('errors.layout')

@php
    // Show the reason when the app gave a specific one (e.g. a disabled module); hide framework defaults
    $reason = trim((string) $exception->getMessage());
    $reason = in_array($reason, ['', 'This action is unauthorized.', 'Forbidden', 'Invalid signature.'], true) ? null : $reason;
@endphp

@section('code', '403')
@section('title', 'Access not allowed')
@section('message', 'Your account does not have permission to open this page. If you believe you should have access, please contact your Fleet Management administrator.')

@if ($reason)
    @section('detail', $reason)
@endif
