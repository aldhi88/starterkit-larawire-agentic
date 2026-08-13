@extends('errors::layout')

@section('code', $exception->getStatusCode())
@section('title', __('http-statuses.'.(string) $exception->getStatusCode()))
@section('message', 'Permintaan tidak dapat diproses. Periksa alamat atau akses Anda, lalu coba kembali.')
