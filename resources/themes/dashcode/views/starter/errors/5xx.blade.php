@extends('errors::layout')

@section('code', $exception->getStatusCode())
@section('title', __('http-statuses.'.(string) $exception->getStatusCode()))
@section('message', 'Terjadi kendala pada server. Silakan coba kembali beberapa saat lagi.')
