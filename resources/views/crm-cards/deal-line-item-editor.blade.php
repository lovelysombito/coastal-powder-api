<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="padding:30px">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>Edit Line Items</title>


        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <style>
        .rt-tbody .rt-tr-group {
            border-radius: 0;
            cursor: move;
        }
        </style>
    </head>
    <body class="bg-white">
        <div id="deal-line-item-editor"></div>
    </body>

    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="https://raw.githack.com/SortableJS/Sortable/master/Sortable.js"></script>
</html>
