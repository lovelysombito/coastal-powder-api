@extends('layouts.email-layout')
@section('qc')
	<p>{{$data['content']}}</p>
    <p><b>Job that failed testing: </b>{{$data['job']}}</p>
	<p><b>Failed Reason: </b>{{$data['comment']}}</p>
	<p><b>Redone Job: </b><b><a href="{{$data['link']}}">{{$data['re_done_job']}}</a></b></p>
@endsection