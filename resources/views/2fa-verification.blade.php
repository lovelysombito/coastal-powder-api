<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Coastal Powder</title>
</head>
<body>
    <div class="container-fluid py-5">
        <div class="d-flex justify-content-center ">
            <div class="col-4">
                <div class="card shadow rounded" style="border: none;">
                    <div class="card-body">
                        <div class="logo-content text-center my-2" style="padding: 40px 0">
                            <img src="{{ asset('img/logo.svg') }}" width="100%" >
                        </div>

                        @if(session()->has('message'))
                            <div class="alert alert-success">
                                {{ session()->get('message') }}
                            </div>
                        @else
                            <form action="{{ route('verify', ['token' => $data['token']]) }}" method="post">
                                @csrf
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <input id="code" name="code" type="text" placeholder="Verification Code" class="form-control"  style="border-radius:15px" required>
                                        </div>
                                    </div>
                                </div>

                                @if($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    {{$errors->first()}}
                                </div>
                                @endif
                                <div class="action mt-4"><button id="btn-register" type="submit"
                                    class="submit btn btn-primary btn-block" style="border-radius:15px"> Submit </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>
