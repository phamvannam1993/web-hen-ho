/**
 * Nút hiện / ẩn mật khẩu.
 *
 * Tự tìm mọi <input type="password"> trên trang, bọc lại và gắn nút con mắt.
 * Làm bằng JS để không phải sửa từng biểu mẫu, và khi thêm ô mật khẩu mới
 * ở bất kỳ đâu thì nút cũng tự có.
 *
 * Bỏ qua ô nào có thuộc tính data-no-toggle.
 */
(function () {
    'use strict';

    var EYE_SHOW = '👁';        // đang ẩn -> bấm để xem
    var EYE_HIDE = '🙈';        // đang hiện -> bấm để ẩn

    document.querySelectorAll('input[type=password]').forEach(function (input) {
        if (input.hasAttribute('data-no-toggle') || input.dataset.toggleReady) {
            return;
        }
        input.dataset.toggleReady = '1';

        // Bọc ô nhập để đặt nút nằm chồng lên góc phải
        var wrap = document.createElement('div');
        wrap.className = 'pw-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var btn = document.createElement('button');
        btn.type = 'button';                 // tránh việc bấm nút lại gửi biểu mẫu
        btn.className = 'pw-toggle';
        btn.textContent = EYE_SHOW;
        btn.setAttribute('aria-label', 'Hiện mật khẩu');
        btn.setAttribute('tabindex', '-1');  // bỏ qua khi nhấn Tab, đỡ vướng luồng nhập
        wrap.appendChild(btn);

        btn.addEventListener('click', function () {
            var showing = input.type === 'text';

            // Ghi nhớ vị trí con trỏ để đổi kiểu không làm nhảy về cuối
            var start = input.selectionStart;
            var end = input.selectionEnd;

            input.type = showing ? 'password' : 'text';
            btn.textContent = showing ? EYE_SHOW : EYE_HIDE;
            btn.setAttribute('aria-label', showing ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            btn.classList.toggle('on', !showing);

            input.focus();
            try {
                input.setSelectionRange(start, end);
            } catch (e) {
                // một số trình duyệt không cho đặt lại vị trí ngay sau khi đổi kiểu
            }
        });

        // Rời khỏi ô thì tự ẩn lại cho an toàn, tránh để lộ trên màn hình
        input.addEventListener('blur', function () {
            setTimeout(function () {
                if (document.activeElement !== btn && input.type === 'text') {
                    input.type = 'password';
                    btn.textContent = EYE_SHOW;
                    btn.classList.remove('on');
                }
            }, 120);
        });
    });
})();
