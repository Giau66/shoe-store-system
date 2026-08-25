/**
 * SHOESSTORE - Professional Voucher & Coupon Interactivity
 * Toast notifications, 1-Click Copy, AJAX Save, Category Filters
 */

(function() {
    'use strict';

    // 1. Toast Notification Container Initialization
    function getToastContainer() {
        let container = document.getElementById('voucherToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'voucherToastContainer';
            container.className = 'voucher-toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    // Global Toast Function
    window.showVoucherToast = function(message, type = 'success') {
        const container = getToastContainer();
        const toast = document.createElement('div');
        toast.className = `voucher-toast ${type}`;
        
        let icon = type === 'success' ? 'fa-solid fa-circle-check text-success' : 'fa-solid fa-circle-exclamation text-danger';
        toast.innerHTML = `<i class="${icon} fs-5"></i> <span>${message}</span>`;
        
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    };

    // 2. Global 1-Click Copy Code
    window.copyVoucherCode = function(code, btnElement) {
        if (!code) return;
        
        navigator.clipboard.writeText(code).then(() => {
            showVoucherToast(`Đã sao chép mã <strong>${code}</strong> vào bộ nhớ tạm!`, 'success');
            
            if (btnElement) {
                const originalHtml = btnElement.innerHTML;
                btnElement.innerHTML = '<i class="fa-solid fa-check text-success"></i>';
                btnElement.classList.add('border-success');
                setTimeout(() => {
                    btnElement.innerHTML = originalHtml;
                    btnElement.classList.remove('border-success');
                }, 2000);
            }
        }).catch(() => {
            // Fallback for older browsers
            const tempInput = document.createElement('input');
            tempInput.value = code;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            showVoucherToast(`Đã sao chép mã <strong>${code}</strong>!`, 'success');
        });
    };

    // 3. Global 1-Click AJAX Save Voucher
    window.saveVoucherToWallet = function(voucherId, voucherCode, btnElement) {
        if (!voucherId && !voucherCode) return;

        if (btnElement && btnElement.disabled) return;

        const formData = new FormData();
        if (voucherId) formData.append('voucher_id', voucherId);
        if (voucherCode) formData.append('voucher_code', voucherCode);

        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';
        }

        fetch('api/save-voucher.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showVoucherToast(data.message || 'Lưu voucher thành công!', 'success');
                
                // Update all buttons for this voucher across the page
                const allButtons = document.querySelectorAll(`[data-voucher-id="${voucherId}"], [data-voucher-code="${voucherCode}"]`);
                allButtons.forEach(btn => {
                    if (btn.classList.contains('btn-voucher-save') || btn.classList.contains('btn-save-voucher')) {
                        btn.classList.add('saved');
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Đã Lưu';
                    }
                });

                // Dispatch global event for other components
                window.dispatchEvent(new CustomEvent('voucherSaved', { detail: { id: voucherId, code: voucherCode } }));
            } else {
                if (data.require_login) {
                    showVoucherToast(data.message || 'Vui lòng đăng nhập để lưu voucher!', 'error');
                    setTimeout(() => {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname);
                    }, 1200);
                } else {
                    showVoucherToast(data.message || 'Không thể lưu voucher.', 'error');
                    if (btnElement) {
                        btnElement.disabled = false;
                        btnElement.innerHTML = '<i class="fa-solid fa-bookmark me-1"></i> Lưu Mã';
                    }
                }
            }
        })
        .catch(err => {
            console.error(err);
            showVoucherToast('Lỗi kết nối máy chủ khi lưu voucher.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = '<i class="fa-solid fa-bookmark me-1"></i> Lưu Mã';
            }
        });
    };

    // 4. Attach Event Listeners on DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        // Delegate Save Button Clicks
        document.body.addEventListener('click', function(e) {
            const saveBtn = e.target.closest('.btn-voucher-save, .btn-save-voucher');
            if (saveBtn && !saveBtn.classList.contains('saved') && !saveBtn.disabled) {
                e.preventDefault();
                const vid = saveBtn.getAttribute('data-voucher-id');
                const vcode = saveBtn.getAttribute('data-voucher-code');
                window.saveVoucherToWallet(vid, vcode, saveBtn);
            }

            // Delegate Copy Button Clicks
            const copyBtn = e.target.closest('.btn-voucher-copy, .copy-voucher-btn, .copy-btn, .voucher-code-badge');
            if (copyBtn && !saveBtn) {
                e.preventDefault();
                const code = copyBtn.getAttribute('data-code') || copyBtn.innerText.trim();
                window.copyVoucherCode(code, copyBtn);
            }
        });

        // Search & Filter in All Vouchers Modal
        const voucherSearchInput = document.getElementById('modalVoucherSearch');
        if (voucherSearchInput) {
            voucherSearchInput.addEventListener('input', function() {
                const keyword = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('#modalVouchersList .modal-voucher-col');
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (text.includes(keyword)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });

})();
