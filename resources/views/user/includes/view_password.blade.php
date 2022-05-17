<div class="row">
    <div class="col">
        <form action="{{ route('userAccountChangePassword') }}" method="post">
            @csrf

            <div class="mb-3">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input type="password" class="form-control" name="password" value="" id="password" required autofocus>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">{{ __('New Password') }}</label>
                <input type="password" class="form-control" name="new_password" value="" id="new_password" required>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">{{ __('Confirm Password') }}</label>
                <input type="password" class="form-control" name="confirm_password" value="" id="confirm_password" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Update Password') }}</button>
            </div>
        </form>
    </div>
</div>
