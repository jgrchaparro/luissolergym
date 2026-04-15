function showToast(type, message) {
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    var container = $('#toast-container');
    if (!container.length) {
        container = $('<div id="toast-container" class="toast-container"></div>').appendTo('body');
    }
    var toast = $(
        '<div class="toast-message toast-' + type + '">' +
            '<i class="fa ' + icon + ' fa-lg"></i>' +
            '<span>' + message + '</span>' +
            '<button class="toast-close">&times;</button>' +
        '</div>'
    );
    container.append(toast);

    toast.find('.toast-close').on('click', function () {
        removeToast(toast);
    });

    setTimeout(function () {
        removeToast(toast);
    }, 4000);
}

function removeToast(toast) {
    toast.css('animation', 'toastSlideOut 0.3s ease forwards');
    setTimeout(function () { toast.remove(); }, 300);
}
