<?php

namespace WeSoonNet\LaravelPlus\Services\QCloud;

use TencentCloud\Vms\V20200902\VmsClient;
use TencentCloud\Vms\V20200902\Models\SendTtsVoiceRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;


class VmsService
{
    public static function send($secretId, $secretKey, $appId, $mobile, $templateId, array $templateParams = [], $playTimes = 3, $sessionContent = '')
    {
        $cred        = new Credential($secretId, $secretKey);
        $httpProfile = new HttpProfile();
        $httpProfile->setReqMethod("POST");
        $httpProfile->setReqTimeout(30);
        $httpProfile->setEndpoint("vms.tencentcloudapi.com");
        $clientProfile = new ClientProfile();
        $clientProfile->setSignMethod("TC3-HMAC-SHA256");
        $clientProfile->setHttpProfile($httpProfile);
        $client                = new VmsClient($cred, "ap-guangzhou", $clientProfile);
        $req                   = new SendTtsVoiceRequest();
        $req->TemplateId       = $templateId;
        $req->TemplateParamSet = $templateParams;
        $req->CalledNumber     = $mobile;
        $req->VoiceSdkAppid    = $appId;
        $req->PlayTimes        = $playTimes;
        $req->SessionContext   = $sessionContent;

        return $client->SendTtsVoice($req);
    }
}
