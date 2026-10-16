@extends('errors.layout')

@php
    $wait = (int) ($exception->getHeaders()['Retry-After'] ?? 60);
@endphp

@section('code', '429')
@section('title', 'Too many attempts')
@section('message', 'You tried too many times in a short period. Please wait ' . ($wait > 90 ? ceil($wait / 60) . ' minutes' : 'about a minute') . ', then try again.')
