@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>{{ $passwordId == 0 ? __('Create Password') : __('Edit Password') }}</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <form method="post" action="{{ route('managePasswordsEditSave', ['id' => $passwordId]) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="label" class="form-label">{{ __('Label') }}</label>
                        <input name="label" type="text" class="form-control @error('label') is-invalid @enderror" id="label" autofocus required value="{{ old('label') ?? ($remotePassword ? $remotePassword->label : '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <input name="description" type="text" class="form-control" id="description" value="{{ old('description') ?? ($remotePassword ? $remotePassword->description : '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="plain_password" class="form-label">{{ __('Encrypt Password') }}</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text">{{ __('Plain Password') }}</span>
                            <input type="text" class="form-control" id="plain_password">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text">{{ __('Public Key') }}</span>
                            <select class="form-select" id="public_key">
                                <option value="">{{ __('Select Public Key') }}</option>
                                @foreach ($publicKeys as $publicKey)
                                    <option value="@php echo base64_encode($publicKey->data) @endphp">{{ $publicKey->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="invalid-feedback mb-2">
                            <span class="d-none invalid-public-key">{{ __('Invalid public key:') }}</span>
                            <span class="d-none invalid-plain-password">{{ __('Invalid plain password:') }}</span>
                            <span class="d-none invalid-general ms-1"></span>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-info d-grid" type="button" id="encrypt_password">{{ __('Encrypt') }}</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="data" class="form-label">{{ __('Encrypted Data') }} <small class="fst-italic">({{ __('You can paste your encrypted data straight into the box, if you have encrypted it offline') }})</small></label>
                        <textarea name="data" class="form-control font-monospace font-smaller @error('data') is-invalid @enderror" rows="10" id="data" required>{{ old('data') ?? ($remotePassword ? $remotePassword->data : '') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="public_key_id" class="form-label">{{ __('Public Key ID') }}</label>
                        <input name="public_key_id" type="text" class="form-control" id="public_key_id" value="{{ old('public_key_id') ?? ($remotePassword ? $remotePassword->public_key_id : '') }}" required>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch switch-on-the-right">
                            <input name="enabled" class="form-check-input float-end" type="checkbox" role="switch" id="enabled" {{ old('enabled') ? 'checked' : ($remotePassword && !$remotePassword->enabled ? '' : 'checked') }} value="1">
                            <label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ asset('/js/lib/openpgp.min.js') }}"></script>

    <script type="text/javascript">
        let pgpManager = {
            lastError: '',

            init: function () {
                $('#encrypt_password').click(async function () {
                    await pgpManager.encryptPassword(
                        $('#plain_password').val().trim(),
                        atob($('#public_key').val().trim()).trim()
                    );
                    return false;
                });
            },

            encryptPassword: async function (text, key) {
                let error = false;
                if (text.length === 0) {
                    error = true;
                    $('#plain_password').addClass('is-invalid');
                }

                if (key.length === 0) {
                    error = true;
                    $('#public_key').addClass('is-invalid');
                }

                if (error) {
                    return false;
                }

                pgpManager.__hideErrors();

                let publicKey = await pgpManager.__readPublicKey(key);
                let plainMessage = await pgpManager.__readMessage(text);

                if (publicKey === false || plainMessage === false) {
                    $('.invalid-feedback').addClass('d-block');
                    $('.invalid-general').removeClass('d-none').text(pgpManager.lastError);
                    let classToShow = publicKey === false ? '.invalid-public-key' : '.invalid-plain-password';
                    $(classToShow).removeClass('d-none');
                    return false;
                }

                let encryptedText = await pgpManager.__encrypt(plainMessage, publicKey);
                if (encryptedText === false) {
                    $('.invalid-feedback').addClass('d-block');
                    $('.invalid-general').removeClass('d-none').text(pgpManager.lastError);
                    return false;
                }

                pgpManager.__hideErrors();

                $('#data').val(encryptedText.trim());
                $('#public_key_id').val(publicKey.getKeyID().toHex().toUpperCase());
                return true;
            },

            __hideErrors: function () {
                $('#plain_password, #public_key').removeClass('is-invalid');
                $('.invalid-feedback').removeClass('d-block');
                $('.invalid-feedback span').addClass('d-none');
            },

            __readPublicKey: async function (key) {
                let publicKey;
                try {
                    publicKey = await openpgp.readKey({ armoredKey: key });
                } catch (e) {
                    pgpManager.lastError = e.message;
                    return false;
                }
                return publicKey;
            },

            __readMessage: async function (text) {
                let plainMessage;
                try {
                    plainMessage = await openpgp.createMessage({ text: text });
                } catch (e) {
                    pgpManager.lastError = e.message;
                    return false;
                }
                return plainMessage;
            },

            __encrypt: async function (message, publicKey) {
                let encryptedText;
                try {
                    encryptedText = await openpgp.encrypt({
                        message: message,
                        encryptionKeys: publicKey
                    });
                } catch (e) {
                    pgpManager.lastError = e.message;
                    return false;
                }
                return encryptedText;
            }
        };

        $(document).ready(function () {
            pgpManager.init();
        });
    </script>
@endsection
