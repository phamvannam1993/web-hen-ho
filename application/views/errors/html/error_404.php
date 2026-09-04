<?php
defined('BASEPATH') or exit('No direct script access allowed');

/* Trang 404 chạy ngoài bố cục chính nên không dùng được view layout hay helper;
   toàn bộ kiểu dáng để thẳng trong tệp, đường dẫn dùng dạng tương đối. */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Không tìm thấy trang - Saigon Cupid</title>
    <style>
        :root {
            --pink: #e91e8c; --pink-dark: #c2126f;
            --ink: #2c2c2c; --muted: #6d6d6d; --line: #e8eaee;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--ink);
            background: radial-gradient(1100px 520px at 50% -10%, #ffe6f4 0%, #f7f8fb 55%, #f2f4f8 100%);
        }

        .err { width: 100%; max-width: 560px; text-align: center; }

        /* Trái tim vỡ: hai nửa lệch nhau, thay cho ảnh minh hoạ bên ngoài */
        .err-art { position: relative; height: 132px; margin-bottom: 6px; }
        .err-art svg { position: absolute; left: 50%; top: 0; width: 132px; height: 132px; transform: translateX(-50%); }
        .err-art .half-l { animation: nghieng-trai 3.4s ease-in-out infinite; }
        .err-art .half-r { animation: nghieng-phai 3.4s ease-in-out infinite; }
        @keyframes nghieng-trai  { 0%,100% { transform: translate(-50%, 0) rotate(0); } 50% { transform: translate(-52%, 3px) rotate(-5deg); } }
        @keyframes nghieng-phai  { 0%,100% { transform: translate(-50%, 0) rotate(0); } 50% { transform: translate(-48%, 3px) rotate(5deg); } }
        @media (prefers-reduced-motion: reduce) { .err-art svg { animation: none !important; } }

        .err h1 { margin: 0 0 10px; font-size: 24px; font-weight: 700; line-height: 1.35; }
        .err p  { margin: 0 auto 22px; max-width: 420px; color: var(--muted); font-size: 15px; line-height: 1.65; }

        /* Đặt sau .err p và thêm .err để thắng độ ưu tiên, nếu không cỡ chữ bị đè */
        .err .err-code {
            font-size: 66px; font-weight: 800; letter-spacing: 2px; line-height: 1;
            margin: 0 0 10px;
            background: linear-gradient(120deg, var(--pink), var(--pink-dark));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }

        /* Ô tìm kiếm để người dùng đi tiếp thay vì thoát trang */
        .err-search { display: flex; gap: 8px; max-width: 420px; margin: 0 auto 20px; }
        .err-search input {
            flex: 1 1 auto; min-width: 0; height: 44px; padding: 0 15px;
            border: 1px solid var(--line); border-radius: 999px; background: #fff;
            font-size: 15px; color: var(--ink);
        }
        .err-search input:focus { outline: none; border-color: var(--pink); }
        .err-search button {
            flex: 0 0 auto; height: 44px; padding: 0 22px; border: 0; border-radius: 999px;
            background: var(--pink); color: #fff; font-size: 15px; font-weight: 700; cursor: pointer;
        }
        .err-search button:hover { background: var(--pink-dark); }

        .err-links { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .err-links a {
            display: inline-flex; align-items: center; height: 42px; padding: 0 20px;
            border: 1px solid var(--line); border-radius: 999px; background: #fff;
            color: var(--ink); font-size: 14.5px; font-weight: 600; text-decoration: none;
        }
        .err-links a:hover { border-color: var(--pink); color: var(--pink); }
        .err-links a.chinh { background: var(--pink); border-color: var(--pink); color: #fff; }
        .err-links a.chinh:hover { background: var(--pink-dark); border-color: var(--pink-dark); }

        @media (max-width: 480px) {
                .err .err-code { font-size: 54px; }
            .err h1 { font-size: 20px; }
            .err-art { height: 108px; }
            .err-art svg { width: 108px; height: 108px; }
            .err-links a { height: 40px; padding: 0 16px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <main class="err">
        <div class="err-art" aria-hidden="true">
            <svg class="half-l" viewBox="0 0 48 48" fill="none">
                <path d="M23 41S6 30.6 6 19.8A7.9 7.9 0 0 1 20.4 15L23 18.6l-4 5.4 4 4-2.4 4.6L23 41z"
                      fill="#f9c9e2" stroke="#e91e8c" stroke-width="1.6" stroke-linejoin="round"/>
            </svg>
            <svg class="half-r" viewBox="0 0 48 48" fill="none">
                <path d="M25 41s17-10.4 17-21.2A7.9 7.9 0 0 0 27.6 15L25 18.6l4 5.4-4 4 2.4 4.6L25 41z"
                      fill="#fbdcec" stroke="#e91e8c" stroke-width="1.6" stroke-linejoin="round"/>
            </svg>
        </div>

        <p class="err-code">404</p>
        <h1>Không tìm thấy trang bạn cần</h1>
        <p>Trang này có thể đã được đổi tên, gỡ bỏ, hoặc đường dẫn bị gõ nhầm.
           Bạn thử tìm lại hoặc quay về trang chủ nhé.</p>

        <form class="err-search" method="get" action="/tim-kiem" role="search">
            <input type="text" name="q" placeholder="Tìm thành viên theo tên..." aria-label="Từ khoá tìm kiếm">
            <button type="submit">Tìm</button>
        </form>

        <nav class="err-links">
            <a class="chinh" href="/">Về trang chủ</a>
            <a href="/thanh-vien">Thành viên</a>
            <a href="/hen-ho">Hẹn hò</a>
            <a href="/tam-su">Tâm sự</a>
            <a href="/lien-he">Liên hệ</a>
        </nav>
    </main>
</body>
</html>
