/**
 * Custom Centered Dialogs & SweetAlert2 Enhancements for Shoes Store
 */
(function() {
    'use strict';

    // 1. Popup xác nhận xóa 1 sản phẩm khỏi giỏ hàng
    window.confirmDeleteCartItem = function(form, itemName = 'sản phẩm này') {
        if (typeof Swal === 'undefined') {
            return confirm('Bạn có chắc muốn xóa ' + itemName + ' khỏi giỏ hàng?');
        }
        Swal.fire({
            title: 'Xóa khỏi giỏ hàng?',
            html: `Bạn có chắc muốn xóa <b>${itemName}</b> khỏi giỏ hàng không?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Xóa ngay',
            cancelButtonText: 'Giữ lại',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0',
                confirmButton: 'rounded-pill px-4 py-2 fw-bold',
                cancelButton: 'rounded-pill px-4 py-2 fw-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    };

    // 2. Popup xác nhận xóa toàn bộ giỏ hàng
    window.confirmClearCart = function(form) {
        if (typeof Swal === 'undefined') {
            return confirm('Bạn có chắc muốn xóa tất cả sản phẩm trong giỏ hàng?');
        }
        Swal.fire({
            title: 'Xóa toàn bộ giỏ hàng?',
            text: 'Tất cả sản phẩm đã chọn sẽ bị xóa khỏi giỏ hàng của bạn.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Xóa tất cả',
            cancelButtonText: 'Hủy bỏ',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0',
                confirmButton: 'rounded-pill px-4 py-2 fw-bold',
                cancelButton: 'rounded-pill px-4 py-2 fw-bold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    };

    // 3. Popup thông báo nổi chính giữa màn hình (Center Toast / Modal)
    window.showCenterAlert = function(type = 'success', title = 'Thành công', message = '', timer = 2200) {
        if (typeof Swal === 'undefined') {
            alert(title + (message ? ': ' + message : ''));
            return;
        }
        Swal.fire({
            icon: type,
            title: title,
            text: message,
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0'
            }
        });
    };
})();
