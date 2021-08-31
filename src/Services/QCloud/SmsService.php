<?php

namespace WeSoonNet\LaravelPlus\Services\QCloud;

use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Sms\V20210111\SmsClient;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;


class SmsService
{
    public static function send($secretId, $secretKey, $appId, $mobile, $templateId, $sign, array $templateParams = [], $sessionContent = '')
    {
        $cred        = new Credential($secretId, $secretKey);
        $httpProfile = new HttpProfile();
        $httpProfile->setReqMethod("GET");
        $httpProfile->setReqTimeout(30);
        $httpProfile->setEndpoint("sms.tencentcloudapi.com");
        $clientProfile = new ClientProfile();
        $clientProfile->setSignMethod("TC3-HMAC-SHA256");
        $clientProfile->setHttpProfile($httpProfile);
        $client                = new SmsClient($cred, "ap-guangzhou", $clientProfile);
        $req                   = new SendSmsRequest();
        $req->SmsSdkAppId      = $appId;
        $req->SignName         = $sign;
        $req->PhoneNumberSet   = ["+86{$mobile}"];
        $req->SessionContext   = $sessionContent;
        $req->TemplateId       = $templateId;
        $req->TemplateParamSet = $templateParams;

        return $client->SendSms($req);
    }
}
