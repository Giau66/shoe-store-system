document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('button,.btn,.nav-link').forEach(el=>{
  if(getComputedStyle(el).position==='static')el.style.position='relative';el.style.overflow='hidden';
  el.addEventListener('pointerdown',e=>{el.classList.add('ui-touch');const r=el.getBoundingClientRect(),s=Math.max(r.width,r.height),x=document.createElement('span');x.className='ui-ripple';x.style.width=x.style.height=s+'px';x.style.left=(e.clientX-r.left-s/2)+'px';x.style.top=(e.clientY-r.top-s/2)+'px';el.appendChild(x);setTimeout(()=>x.remove(),620)});
  ['pointerup','pointercancel','pointerleave'].forEach(n=>el.addEventListener(n,()=>el.classList.remove('ui-touch')))
 });
 document.querySelectorAll('.card,.table-responsive').forEach((el,i)=>{el.classList.add('admin-reveal');el.style.animationDelay=Math.min(i,8)*45+'ms'});
 document.addEventListener('click',e=>{const side=document.getElementById('sidebar');if(innerWidth<769&&side?.classList.contains('show')&&!side.contains(e.target)&&!e.target.closest('#sidebarToggle'))side.classList.remove('show')});
});

/**
 * UNIVERSAL ANTI-DOUBLE-SUBMIT GUARD
 * Intercepts all form submissions and prevents duplicate requests.
 * Automatically disables submit buttons while the form is submitting.
 */
(function() {
    function attachAntiDoubleSubmit() {
        document.querySelectorAll('form').forEach(function(form) {
            if (form.dataset.antiDoubleSubmit) return; // already attached
            form.dataset.antiDoubleSubmit = '1';

            form.addEventListener('submit', function(e) {
                // Bỏ qua các form AJAX tự quản lý submit
                if (form.dataset.ajaxForm === '1' || form.id === 'eventAjaxForm' || form.id === 'addProductEventForm' || form.id === 'linkVoucherEventForm') {
                    return;
                }

                var submitBtns = form.querySelectorAll('[type="submit"], button:not([type="button"])');
                var alreadySubmitting = form.dataset.isSubmitting === '1';
                if (alreadySubmitting) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
                form.dataset.isSubmitting = '1';
                submitBtns.forEach(function(btn) {
                    btn.disabled = true;
                    btn.dataset.origText = btn.innerHTML;
                    if (!btn.dataset.noLoadingText) {
                        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang xử lý...';
                    }
                });
                // Reset sau 6 giây nếu không có phản hồi (fallback)
                setTimeout(function() {
                    form.dataset.isSubmitting = '0';
                    submitBtns.forEach(function(btn) {
                        btn.disabled = false;
                        if (btn.dataset.origText) btn.innerHTML = btn.dataset.origText;
                    });
                }, 6000);
            }, true); // capture phase để chạy trước các listener khác
        });
    }

    // Chạy lần đầu khi trang load
    document.addEventListener('DOMContentLoaded', attachAntiDoubleSubmit);

    // Chạy lại mỗi khi SPA router tải trang mới
    window.addEventListener('adminPageLoaded', function() {
        setTimeout(attachAntiDoubleSubmit, 150);
    });

    // Hàm helper để reset form sau khi AJAX hoàn thành
    window.resetFormSubmit = function(formOrId) {
        var form = typeof formOrId === 'string' ? document.getElementById(formOrId) : formOrId;
        if (!form) return;
        form.dataset.isSubmitting = '0';
        form.querySelectorAll('[type="submit"]').forEach(function(btn) {
            btn.disabled = false;
            if (btn.dataset.origText) {
                btn.innerHTML = btn.dataset.origText;
                delete btn.dataset.origText;
            }
        });
    };
})();
