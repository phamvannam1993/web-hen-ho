/**
 * Khởi tạo CKEditor cho các ô soạn thảo trong khu quản trị.
 *
 * Cách dùng: thêm class "rich-editor" vào thẻ <textarea>.
 * Muốn thanh công cụ rút gọn thì dùng thêm class "rich-editor-basic".
 */
(function () {
    'use strict';

    if (typeof CKEDITOR === 'undefined') {
        return; // chưa nạp được thư viện thì để nguyên textarea thường
    }

    // Cho phép mọi thẻ HTML người soạn nhập vào, không tự lọc bớt
    CKEDITOR.config.allowedContent = true;
    CKEDITOR.config.language = 'vi';
    CKEDITOR.config.entities = false;      // giữ nguyên tiếng Việt, không đổi thành &agrave;
    CKEDITOR.config.removePlugins = 'elementspath';
    CKEDITOR.config.resize_enabled = true;

    var toolbarFull = [
        { name: 'styles', items: ['Format', 'FontSize'] },
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'TextColor', 'BGColor', '-', 'RemoveFormat'] },
        { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'] },
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'insert', items: ['Image', 'Table', 'HorizontalRule'] },
        { name: 'tools', items: ['Maximize', 'Source'] }
    ];

    var toolbarBasic = [
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', '-', 'RemoveFormat'] },
        { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'tools', items: ['Source'] }
    ];

    document.querySelectorAll('textarea.rich-editor').forEach(function (el) {
        var basic = el.classList.contains('rich-editor-basic');

        CKEDITOR.replace(el, {
            toolbar: basic ? toolbarBasic : toolbarFull,
            height: basic ? 180 : 420,
            // Tải ảnh lên qua endpoint riêng của khu quản trị
            filebrowserImageUploadUrl: el.getAttribute('data-upload-url') || '',
            filebrowserUploadMethod: 'form'
        });
    });

    // Đồng bộ nội dung từ editor về textarea trước khi gửi form,
    // nếu không dữ liệu vừa soạn sẽ không được lưu.
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            for (var name in CKEDITOR.instances) {
                if (Object.prototype.hasOwnProperty.call(CKEDITOR.instances, name)) {
                    CKEDITOR.instances[name].updateElement();
                }
            }
        });
    });
})();
