@extends('layouts.email-layout')
@section('overdue-email')
	<div style="text-align: left">
		<p>Dear {{$data['name']}}</p>
		<p style="margin-top: 30px; margin-bottom: 30px">This is an automated email, please click <a href="{{$data['url']}}/overdue">**here**</a> to view jobs to be actioned. They are either due to be actioned or are overdue.</p>
		<p>Sincerely,</p>
		<p>Sync App</p>
	</div>
@endsection