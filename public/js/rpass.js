let ClipboardManager = {
    iconReplaceTimeout: 3000,

    init: function () {
        let clipboard = new ClipboardJS(
            '.copy-to-clipboard',
            {
                text: function (trigger) {
                    // Get data and trim.
                    let parentElement = $($(trigger).data('clipboard-target'));
                    if (!parentElement) {
                        return 'RemotePassword - Error copying data to clipboard.';
                    }

                    if ($(parentElement).find('.d-none').length > 0) {
                        parentElement = $(parentElement).children(':visible');
                    }

                    let text = $(parentElement).text().trim();

                    // Check if there's a clipboard icon and replace it with a check.
                    ClipboardManager.setTickIcon(trigger);

                    return text;
                }
            }
        )
    },

    setTickIcon: function (trigger) {
        $(trigger).find('.icon-copy').addClass('d-none');
        $(trigger).find('.icon-copied').removeClass('d-none');
        setTimeout(ClipboardManager.setClipboardIcon, ClipboardManager.iconReplaceTimeout, trigger);
    },

    setClipboardIcon: function (trigger) {
        $(trigger).find('.icon-copy').removeClass('d-none');
        $(trigger).find('.icon-copied').addClass('d-none');
    }
};

$(document).ready(function () {
    $('.confirm-delete').click(function () {
        let formToSubmit = $(this).closest('form').attr('id');
        let text = $(this).data('text') !== undefined ? $(this).data('text') : '';
        if (text.length > 0) {
            $('#delete-confirmation-text').text(text);
        }
        $('#delete-form-to-submit').val(formToSubmit);
        $('#delete-confirmation-box').modal('show');
        return false;
    });

    $('.delete-confirmation-button').click(function () {
        let formToSubmit = $('#delete-form-to-submit').val();
        $('#' + formToSubmit).submit();
        $('#delete-confirmation-box').modal('hide');
    });

    $('.submit-on-click').click(function () {
        $(this).closest('form').submit();
        return false;
    });

    $('.select-navigate-on-change').change(function () {
        window.location = $(this).val();
        return true;
    });

    $('.log-toggle-info').click(function () {
        let box = $('#log-info-' + $(this).data('log-id'));
        if (!box) {
            return false;
        }
        if ($(box).hasClass('d-none')) {
            $(box).removeClass('d-none');
        } else {
            $(box).addClass('d-none');
        }
        return false;
    });

    $('.select-multi').each(function () {
        new Choices($(this)[0], { allowHTML: true, removeItemButton: true, shouldSort: false });
    });

    ClipboardManager.init();
});
