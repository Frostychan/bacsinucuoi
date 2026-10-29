<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Kiểm tra xem đã đăng nhập chưa VÀ vai trò có khớp với role yêu cầu hay không
        if (auth()->check() && auth()->user()->role === $role) {
            return $next($request); // Hợp lệ -> cho phép đi tiếp
        }
        // Không đúng quyền -> Chặn lại và báo lỗi HTTP 403 Forbidden
        abort(403, 'Bạn không có quyền truy cập trang này.');
    }
}
