<?php

namespace plugin\sms\api;

use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use plugin\admin\app\model\Option;
use Overtrue\EasySms\EasySms;
use support\exception\BusinessException;

class Sms
{

    /**
     * Option 表的name字段值
     */
    const OPTION_NAME = 'sms_setting';

    /**
     * 发送短信
     * @param string|array $to
     * @param array $data
     * @param array $gateways
     * @return void
     * @throws BusinessException
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     */
    public static function send($to, array $data, array $gateways = [])
    {
        $sms = static::getSms();
        $sms->send($to, $data, $gateways);
    }

    /**
     * Get Sms
     * @return EasySms
     * @throws BusinessException
     */
    public static function getSms(): EasySms
    {
        if (!class_exists(EasySms::class)) {
            throw new BusinessException('请执行 composer require overtrue/easy-sms 并重启');
        }
        $config = static::getConfig();
        if (!$config) {
            throw new BusinessException('未设置SMS配置');
        }
        return new EasySms($config);
    }

    /**
     * 获取配置
     * @return array
     */
    public static function getConfig(): array
    {
        $config = Option::where('name', static::OPTION_NAME)->value('value');
        return $config ? json_decode($config, true) : [];
    }

}