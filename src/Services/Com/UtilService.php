<?php

namespace WeSoonNet\LaravelPlus\Services\Com;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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
     *
     * @return UploadedFile|string
     * @throws \Exception
     */
    public static function uploadToTemp(UploadedFile $file)
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

        return "uploadfiles/tmp/{$filename}";
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
     * @param  string  $file
     * @param  string  $newFile
     *
     * @return string
     */
    public static function moveTmpFileToDir(string $file, string $newFile)
    {
        if (!is_string($file) || empty($file))
        {
            return '';
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

        return $newFile;
    }

    /**
     * 生成18位随机数
     *
     * @return string
     */
    public static function no(string $date_format = 'Ymd', int $length = 18)
    {
        return str_pad(date('Ymd') . self::uniqid(), 18, "0");
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
     * 生成唯一数字序号
     *
     * @param  string  $key
     * @param  int     $start
     *
     * @return int|mixed
     */
    public static function sn(string $key, int $start = 1)
    {
        $key = md5($key);

        if (!Cache::has($key))
        {
            Cache::set($key, $start);
        }
        else
        {
            $start = ((int) Cache::get($key)) + 1;

            Cache::set($key, $start);
        }

        return $start;
    }

    /**
     * 随机字符串
     *
     * @param  int   $length
     * @param  bool  $containNumber
     *
     * @return string
     */
    public static function randomChars(int $length = 4, bool $containNumber = true)
    {
        $chars = $containNumber ? '0123456789abcdefghijkmnpqrstuvwxyz' : 'abcdefghijkmnpqrstuvwxyz';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 1, $length);
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
     * @param $file
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

    /**
     *
     * 金额格式化
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

    /**
     * 字符串加掩码
     *
     * @param          $var
     * @param  int     $start
     * @param  int     $length
     * @param  string  $char
     *
     * @return mixed
     */
    public static function mask($var, $start = 4, $length = 4, $char = '*')
    {
        return substr_replace($var, str_repeat($char, $length), $start, $length);
    }

    /**
     * 正则匹配规则
     *
     * @param $rule
     * @param $value
     *
     * @return false|int
     */
    public static function regex($rule, $value)
    {
        $rules   = [
            'username' => "/^\w{4,16}$/i",
            'password' => "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,16}$/",
            'captcha'  => "/^[0-9]{4,6}$/",
            'mobile'   => "/^1[0123456789][0-9]{9}$/",
            'email'    => "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i",
        ];
        $pattern = $rules[$rule] ?? $rule;

        return preg_match($pattern, $value);
    }

    /**
     * 删除字符串中的表情
     *
     * @param $str
     *
     * @return string|string[]|null
     */
    public static function removeEmoji($str)
    {
        $str = preg_replace_callback('/./u',
            function (array $match)
            {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
    }

    /**
     * 格式化成过去时间
     *
     * @param  int  $time
     *
     * @return string
     */
    public static function getPastTime(int $time)
    {
        $t = time() - $time;
        $f = [
            '31536000' => '年',
            '2592000'  => '个月',
            '604800'   => '星期',
            '86400'    => '天',
            '3600'     => '小时',
            '60'       => '分钟',
            '1'        => null,
        ];
        foreach ($f as $k => $v)
        {
            if (0 != $c = floor($t / (int) $k))
            {
                return $v ? ($c . $v . '前') : '刚刚';
            }
        }
    }

    /**
     * 数字转字母ID
     *
     * @param        $in
     * @param  bool  $to_num
     * @param  bool  $pad_up
     * @param  null  $pass_key
     *
     * @return float|int|string
     */
    public static function alphaID($in, $to_num = false, $pad_up = false, $pass_key = null)
    {
        $out   = '';
        $index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base  = strlen($index);

        if ($pass_key !== null)
        {
            // Although this function's purpose is to just make the
            // ID short - and not so much secure,
            // with this patch by Simon Franz (http://blog.snaky.org/)
            // you can optionally supply a password to make it harder
            // to calculate the corresponding numeric ID

            for ($n = 0; $n < strlen($index); $n++)
            {
                $i[] = substr($index, $n, 1);
            }

            $pass_hash = hash('sha256', $pass_key);
            $pass_hash = (strlen($pass_hash) < strlen($index) ? hash('sha512', $pass_key) : $pass_hash);

            for ($n = 0; $n < strlen($index); $n++)
            {
                $p[] = substr($pass_hash, $n, 1);
            }

            array_multisort($p, SORT_DESC, $i);
            $index = implode($i);
        }

        if ($to_num)
        {
            // Digital number  <<--  alphabet letter code
            $len = strlen($in) - 1;

            for ($t = $len; $t >= 0; $t--)
            {
                $bcp = bcpow($base, $len - $t);
                $out = $out + strpos($index, substr($in, $t, 1)) * $bcp;
            }

            if (is_numeric($pad_up))
            {
                $pad_up--;

                if ($pad_up > 0)
                {
                    $out -= pow($base, $pad_up);
                }
            }
        }
        else
        {
            // Digital number  -->>  alphabet letter code
            if (is_numeric($pad_up))
            {
                $pad_up--;

                if ($pad_up > 0)
                {
                    $in += pow($base, $pad_up);
                }
            }

            for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; $t--)
            {
                $bcp = bcpow($base, $t);
                $a   = floor($in / $bcp) % $base;
                $out = $out . substr($index, $a, 1);
                $in  = $in - ($a * $bcp);
            }
        }

        return $out;
    }
}
