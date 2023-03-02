<?php

namespace plugin\sms\app\admin\model;

use plugin\admin\app\model\Option;
use plugin\sms\api\Sms;

/**
 * 短信模版相关
 */
class Template
{
    /**
     * 获取模版
     * @param $gateway
     * @param $name
     * @return mixed|null
     */
    public static function get($gateway, $name)
    {
        $config = Sms::getConfig();
        return $config['gateways'][$gateway]['templates'][$name] ?? null;
    }

    /**
     * 保存模版
     * @param $gateway
     * @param $name
     * @param $value
     * @return void
     */
    public static function save($gateway, $name, $value)
    {
        $config = Sms::getConfig();
        $config['gateways'][$gateway]['templates'][$name] = $value;
        $optionName = Sms::OPTION_NAME;
        if (!$option = Option::where('name', $optionName)->first()) {
            $option = new Option;
        }
        $option->name = $optionName;
        $option->value = json_encode($config, JSON_UNESCAPED_UNICODE);
        $option->save();
    }

    /**
     * 删除模版
     * @param $gateway
     * @param array $names
     * @return void
     */
    public static function delete($gateway, array $names)
    {
        $config = Sms::getConfig();
        foreach ($names as $name) {
            unset($config['gateways'][$gateway]['templates'][$name]);
        }
        $optionName = Sms::OPTION_NAME;
        if (!$option = Option::where('name', $optionName)->first()) {
            $option = new Option;
        }
        $option->name = $optionName;
        $option->value = json_encode($config, JSON_UNESCAPED_UNICODE);
        $option->save();
    }
    
}
