<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class ImageUpload
{
    /**
     * 全站图片都放在 public/images/ 下，这里按业务子目录归档：
     * images/avatars、images/restaurants、images/recipes、images/notes
     *
     * 把上传的图片存到 public/images/{$folder} 下，返回可直接访问的路径
     */
    public static function store(?UploadedFile $file, string $folder): ?string
    {
        if (! $file) {
            return null;
        }

        $dir = public_path('images/'.$folder);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);

        return '/images/'.$folder.'/'.$name;
    }
}
