@inject('bladeHelper', 'App\Services\BladeHelper')
@php
    $formats = [
        'raw' => 'Raw',
        'base64' => 'Base64',
        'json' => 'JSON',
        'xml' => 'XML'
    ];
@endphp
<div class="row mb-3">
    <div class="col">
        <h4 class="d-inline text-nowrap">{{ __('Add to Local Storage') }}</h4>
    </div>
    <div class="col text-end">
        <button class="copy-to-clipboard btn btn-outline-primary" data-clipboard-target="#add-to-local-storage">
            <span class="icon-copy">{{ __('copy') }}</span>
            <span class="icon-copied d-none"><i class="fa-solid fa-check"></i></span>
        </button>
    </div>
</div>
<div class="row mb-3">
    <div class="col">
        <div class="mt-2 border border-primary rounded p-2">
            <div class="font-monospace access-commands" id="add-to-local-storage">{{ $bladeHelper->generateScriptAddCommand($password) }}</div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col">
        <h4 class="d-inline text-nowrap">{{ __('Direct Access URL') }}</h4>
    </div>
    <div class="col text-end">
        <button class="copy-to-clipboard btn btn-outline-primary" data-clipboard-target="#direct-access-url">
            <span class="icon-copy">{{ __('copy') }}</span>
            <span class="icon-copied d-none"><i class="fa-solid fa-check"></i></span>
        </button>
        <div class="d-md-inline-block d-none">
            <select class="form-select d-inline w-auto btn-outline-primary" id="select-access-format">
                @foreach ($formats as $format => $label)
                    <option value="{{ $format }}">{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="d-md-none">
            <select class="form-select btn-outline-primary" id="select-access-format">
                @foreach ($formats as $format => $label)
                    <option value="{{ $format }}">{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col">
        <div class="mt-2 border border-primary rounded p-2">
            <div class="font-monospace access-commands" id="direct-access-url">
                @foreach ($formats as $format => $label)
                    <span class="access-format-{{ $format }} d-none">{{ $bladeHelper->generateDirectAccessUrl($password, $format) }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('#select-access-format, .access-format').change(function () {
                $('#direct-access-url').find('span').addClass('d-none');
                $('#direct-access-url').find('.access-format-' + $(this).val()).removeClass('d-none');
            });

            $('#select-access-format').trigger('change');
        });
    </script>
@endsection
