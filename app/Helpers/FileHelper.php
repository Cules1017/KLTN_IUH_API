<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileHelper
{
    /**
     * Lưu hình ảnh vào local storage và trả về đường dẫn
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $prefix
     * @return string
     */
    public static function saveImage(UploadedFile $file, $directory = 'avatars', $prefix = 'avatar')
    {
        // Tạo tên tệp mới để tránh trùng lặp
        $fileName = $prefix . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Lưu hình ảnh vào thư mục công cộng
        $path = $file->storeAs("public/{$directory}", $fileName);

        // Trả về đường dẫn đầy đủ
        return asset(Storage::url($path));
    }
}
