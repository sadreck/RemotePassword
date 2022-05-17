<!-- Modal delete confirmation box -->
<div class="modal fade" id="delete-confirmation-box" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="delete-confirmation-text">
                {{ __('Are you sure you want to delete this item?') }}
            </div>
            <div class="modal-footer">
                <input type="hidden" id="delete-form-to-submit" value="">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger delete-confirmation-button">Delete</button>
            </div>
        </div>
    </div>
</div>
