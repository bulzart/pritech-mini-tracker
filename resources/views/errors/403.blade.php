@extends('errors.layout')

@section('title', 'Forbidden')
@section('code', '403')
@section('heading', 'Forbidden')
@section('message', "You don't have permission to do that. Only a project's owner can edit or delete it.")
