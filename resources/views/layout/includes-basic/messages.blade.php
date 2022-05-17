@if ($errors->any() or Session::has('error'))
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                @foreach ($errors->all() as $error)
                    <div class="alert alert-danger">{{ $error }}</div>
                @endforeach

                @if (Session::has('error'))
                    <div class="alert alert-danger">{{ Session::get('error') }}</div>
                @endif
            </div>
        </div>
    </div>
@endif

@if (Session::has('success'))
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success">{{ Session::get('success') }}</div>
            </div>
        </div>
    </div>
@endif
