<?php

namespace WeSoonNet\LaravelPlus\Services\Com;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;

class UtilService
{
    /**
     * html文本相对路径替换成绝对路径
     *
     * @param  string  $html  HTML
     *
     * @return mixed|string
     */
    public static function htmlRelToAbsPath(string $html)
    {
        if (empty($html))
        {
            return $html;
        }

        $html = str_replace('src="unsafe:..', 'src="..', $html);
        $html = str_replace('src="..', 'src="', $html);;
        $preg = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
        preg_match_all($preg, $html, $files);

        if ($files && isset($files[1]) && count($files[1]))
        {
            $files[1] = array_unique($files[1]);
            foreach ($files[1] as $file)
            {
                if (false !== stripos($file, 'uploadfiles'))
                {
                    $newFile = url(trim($file, '/'), [], !config('app.debug', true));
                    $html    = str_replace($file, $newFile, $html);
                }
            }
        }

        return $html;
    }

    /**
     * html绝对路径替换成相对路径
     *
     * @param  string  $html
     *
     * @return mixed|string
     */
    public static function htmlAbsToRelPath(string $html)
    {
        if (empty($html))
        {
            return $html;
        }

        $html = str_replace('src="unsafe:..', 'src="..', $html);
        $html = str_replace('src="..', 'src="', $html);;
        $preg = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
        preg_match_all($preg, $html, $files);

        if ($files && isset($files[1]) && count($files[1]))
        {
            $files[1] = array_unique($files[1]);
            foreach ($files[1] as $file)
            {
                if (false !== stripos($file, 'uploadfiles'))
                {
                    $newFile = trim($file, '/');
                    $newFile = substr($newFile, stripos($newFile, 'uploadfiles'));
                    $html    = str_replace($file, $newFile, $html);
                }
            }
        }

        return $html;
    }

    /**
     * 上传文件到临时目录
     *
     * @param  UploadedFile  $file
     * @param  int|null      $width
     * @param  int|null      $height
     *
     * @return UploadedFile|string
     * @throws \Exception
     */
    public static function uploadToTemp(UploadedFile $file, int $width = null, int $height = null)
    {
        $ext = $file->getClientOriginalExtension();

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf', 'zip']))
        {
            throw new \Exception('不支持的文件类型');
        }

        $filepath = public_path('uploadfiles/tmp');
        $filename = md5(time() . rand(10000, 99999)) . '.' . $ext;

        if (!$file->move($filepath, $filename))
        {
            throw new \Exception('服务器处理文件失败');
        }

        $file = "uploadfiles/tmp/{$filename}";

        if ($width || $height)
        {
            ImageService::resize(public_path($file), $width, $height);
        }

        return $file;
    }

    /**
     * 移动HTML的临时图片文件到指定目录
     *
     * @param  string  $html    HTML
     * @param  string  $newDir  目录路径（相对）
     *
     * @return string HTML
     */
    public static function moveHtmlTmpImagesToDir(string $html, string $newDir)
    {
        $preg = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
        preg_match_all($preg, $html, $files);

        if ($files && isset($files[1]) && count($files[1]))
        {
            foreach ($files[1] as $file)
            {
                if (0 === stripos($file, url('uploadfiles/tmp')))
                {
                    $file = str_replace(url(''), '', $file);
                }
                $file = trim($file, '/');
                if (file_exists(public_path($file)) && 0 === stripos($file, 'uploadfiles/tmp'))
                {
                    if (!file_exists(public_path($newDir)))
                    {
                        @mkdir(public_path($newDir), 0777, true);
                    }
                    $newFile = trim($newDir, '/') . '/' . basename($file);
                    File::move(public_path($file), public_path($newFile));
                    $html = str_replace($file, $newFile, $html);
                }
            }
        }

        return $html;
    }

    /**
     * 移动文件到目录
     *
     * @param  string    $file
     * @param  string    $newFile
     * @param  int|null  $width
     * @param  int|null  $height
     *
     * @return string
     */
    public static function moveTmpFileToDir(string $file, string $newFile, int $width = null, int $height = null)
    {
        if (!is_string($file) || empty($file))
        {
            return null;
        }

        $tmp  = 'uploadfiles/tmp';
        $file = trim($file, '/');

        if (-1 === stripos($file, $tmp))
        {
            return $file;
        }

        $file     = substr($file, stripos($file, $tmp));
        $pathinfo = pathinfo(public_path($newFile));

        if (!isset($pathinfo['extension']))
        {
            $newFile = trim($newFile, '/') . '/' . basename($file);
        }

        $dir = dirname(public_path($newFile));

        if (!file_exists($dir))
        {
            @mkdir($dir, 0755, true);
        }

        if (file_exists(public_path($file)))
        {
            File::move(public_path($file), public_path($newFile));
        }

        ImageService::resize(public_path($file), $width, $height);

        return $newFile;
    }

    /**
     * 生成16位随机数
     *
     * @return string
     */
    public static function no()
    {
        return (date('YmdHis') . rand(10000000, 99999999));
    }

    /**
     * 生成唯一数字ID
     *
     * @return string
     */
    public static function uniqid()
    {
        return crc32(md5(uniqid()));
    }

    /**
     * 当前路由前缀名
     *
     * @param  null  $prefix
     *
     * @return string
     */
    public static function prefix($prefix = null)
    {
        if ($prefix)
        {
            if (is_array($prefix))
            {
                return in_array(strtoupper(Request::route()->getPrefix()), array_map(function ($str)
                {
                    return strtoupper($str);
                }, $prefix));
            }
            else
            {
                return (strtoupper(Request::route()->getPrefix()) === strtoupper($prefix));
            }
        }
        else
        {
            return strtoupper(Request::route()->getPrefix());
        }
    }

    /**
     *  下载文件
     *
     * @param $url
     *
     * @return bool|string
     */
    public static function download($url, $file)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, ""); //加速 这个地方留空就可以了
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        if (!file_exists(dirname($file)))
        {
            @mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, $output);

        return $file;
    }

    public static function week()
    {
        $weeks = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
        return $weeks[date("w")];
    }

    /** 金额格式化
     *
     * @param  int  $price    传入金额单位分
     * @param  int  $decimal  保留小数位数
     *
     * @return float
     */
    public static function priceFormat($price, $decimal = 2)
    {
        return (float) round(((int) $price / 100), $decimal);
    }
}