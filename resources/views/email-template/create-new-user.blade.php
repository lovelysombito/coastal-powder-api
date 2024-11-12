@extends('layouts.email-layout')
@section('user-invitation')
	<div>
		<p style="margin: 0;" style="text-align: center; font-family: 'Calibri';"> 
			<a style="font-weight: bold; font-family: 'Calibri';">{{ $data['name'] }}</a>, Invited you to join coastal powder </p>
	</div>
		<a href="{{ $data['api_url'] }}/process?token={{$data['token']}}" class="button" 
		style="color:white; text-allign:center; display:inline-block; font-size:16px;
			margin-top:10px; cursor: pointer; background-color:royalblue; border-radius:8px;
			text-decoration:none; padding: 10px 40px;">Accept</a>
@endsection