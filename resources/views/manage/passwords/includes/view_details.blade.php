<div class="row row-cols-1 row-cols-sm-2">
    <div class="col mb-3">
        <div class="fw-bold border-bottom">{{ __('Label') }}</div>
        <div>{{ $password->label }}</div>
    </div>

    <div class="col mb-3">
        <div class="fw-bold border-bottom">{{ __('Description') }}</div>
        <div>{{ $password->description }}</div>
    </div>

    <div class="col mb-3">
        <div class="fw-bold border-bottom">{{ __('Enabled') }}</div>
        <div><i class="fa-solid {{ $password->enabled ? 'fa-check text-success' : 'fa-xmark text-danger' }}"></i></div>
    </div>

    <div class="col mb-3">
        <div class="fw-bold border-bottom">{{ __('Encrypted Password') }}</div>
        <div><pre class="font-monospace font-smaller">{{ $password->data }}</pre></div>
    </div>

    <div class="col mb-3">
        <div class="fw-bold border-bottom">{{ __('Public Key ID') }}</div>
        <div>{{ $password->public_key_id }}</div>
    </div>

    <div class="col mb-3">
        <div class="fw-bold border-bottom">{{ __('Checksum') }}</div>
        <div class="font-monospace monospace-smaller">{{ $password->getChecksum() }}</div>
    </div>

    <div class="col mb-3">
        <div class="fw-bold border-bottom">{{ __('Uses') }}</div>
        <div>
            {{ $password->getUses() }}
            <form action="{{ route('managePasswordsResetCount', ['id' => $password->getId()]) }}" method="post" class="d-inline">
                @csrf
                <a href="#" class="ms-2 text-warning float-end submit-on-click" title="{{ __('Reset Use Count') }}"><i class="fa-solid fa-trash"></i></a>
            </form>
        </div>
    </div>
</div>
