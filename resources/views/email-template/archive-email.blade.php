@extends('layouts.email-layout')
@section('archive')
    <table style="width: 100%">
        <tbody>
            <tr>
                <td>
                    <h3>PACKING SLIP</h1>
                    <p>Jack Taylor Group Pty Ltd | Brians Halligan</p>

                    <p><strong>Invoice Date</strong></p>
                    <p>4 Aug 2022</p>

                    <p><strong>Invoice Number</strong></p>
                    <p>ORC1048</p>

                    <p><strong>Reference</strong></p>
                    <p>TU0001</p>

                    <p><strong>Status</strong></p>
                    <p>{{$data['status']}}</p>
                </td>

                <td align="center">
                    <img src="{{ public_path('/img/logo.svg') }}" width="300px" alt="">
                    {{-- <p class="margin-0"><strong>Coastal Powder</strong></p> --}}
                    <p style="margin-top: 10px">23 Main Street<p>
                    <p class="margin-0">MARINEVILLE NSW 2000</p>
                    <p class="margin-0"><strong>ABN</strong></p>
                    <p class="margin-0">11 111 111 138</p>
                </td>
            </tr>


        </tbody>
    </table>
    <table style="width: 100%; margin-top: 50px;">
            <tbody>
                <tr>
                    <td>
                        <strong>Description</strong>
                    </td>
                    <td>
                        <strong>Quantity</strong>
                    </td>
                </tr>
                @foreach($data['line_items'] ?? '' as $item)
                <tr>
                    <td>{{ $item['name'] }}</th>
                    <td>{{ $item['number'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div align="center" style="margin-top: 150px">
            <img src={{$data['signature']}} width="300px" alt="">
            {{-- <p class="margin-0"><strong>Coastal Powder</strong></p> --}}
            <p style="margin-top: 10px"><strong>Signature</strong><p>
        </div>
@endsection

        

        
    