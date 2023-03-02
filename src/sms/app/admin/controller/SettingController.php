<?php

namespace plugin\sms\app\admin\controller;

use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Strategies\OrderStrategy;
use plugin\admin\app\model\Option;
use plugin\sms\api\Sms;
use plugin\sms\app\admin\model\Template;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use function view;

/**
 * 邮件设置
 */
class SettingController
{

    /**
     * 邮件设置页
     * @return Response
     */
    public function index()
    {
        $defaultConfig = config('plugin.sms.sms-default');
        return view('setting/index', [
            'defaultConfig' => $defaultConfig,
        ]);
    }

    /**
     * 获取设置
     * @return Response
     */
    public function get(): Response
    {
        $config = Sms::getConfig();
        if ($config) {
            foreach ($config['default']['gateways'] as $gatewayName) {
                if (isset($config['gateways'][$gatewayName])) {
                    $config['gateways'][$gatewayName]['enable'] = true;
                }
            }
        }
        return json(['code' => 0, 'msg' => 'ok', 'data' => $config]);
    }

    /**
     * 更改设置
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        $defaultConfig = config('plugin.sms.sms-default');
        $config = Sms::getConfig();
        $gatewayName = $request->post('gateway');
        if (!isset($defaultConfig['gateways'][$gatewayName])) {
            return json(['code' => 1, 'msg' => '数据错误']);
        }
        $gateway = $defaultConfig['gateways'][$gatewayName];
        $gatewayConfig = $config['gateways'][$gatewayName] ?? $defaultConfig['gateways'][$gatewayName];
        foreach ($gateway as $field => $value) {
            if ($field === 'name') continue;
            $gatewayConfig[$field] = $request->post($field, '');
        }
        $enable = !empty($request->post('enable'));
        if ($enable) {
            $config['default']['gateways'][] = $gatewayName;
            $config['default']['gateways'] = array_unique($config['default']['gateways']);
        }
        $config['gateways'][$gatewayName] = $gatewayConfig;

        $name = Sms::OPTION_NAME;
        $value = json_encode($config);
        $option = Option::where('name', $name)->first();
        if ($option) {
            Option::where('name', $name)->update(['value' => $value]);
        } else {
            $option = new Option();
            $option->name = $name;
            $option->value = $value;
            $option->save();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 短信模版测试
     * @param Request $request
     * @return Response
     * @throws BusinessException
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     * @throws Throwable
     */
    public function testTemplate(Request $request): Response
    {
        if ($request->method() === 'GET') {
            return view('template/test');
        }

        $gateway = $request->post('gateway');
        $to = $request->post('to');
        $templateName = $request->post('name');
        $data = $request->post('data');
        $data = $data ? json_decode($data, true) : [];
        try {
            Sms::sendByTemplate($to, $templateName, $data, [$gateway]);
        }  catch (Throwable $e) {
            if (method_exists($e, 'getExceptions')) {
                throw new BusinessException(current($e->getExceptions())->getMessage());
            }
            throw $e;
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     */
    public function insertTemplate(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $gateway = $request->post('gateway');
            $name = $request->post('name');
            if (Template::get($gateway, $name)) {
                return json(['code' => 1, 'msg' => '模版已经存在']);
            }
            $templateId = $request->post('template_id');
            $sign = $request->post('sign');
            Template::save($gateway, $name, ['template_id' => $templateId, 'sign' => $sign]);
        }
        return view('template/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     */
    public function updateTemplate(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $gateway = $request->post('gateway');
            $name = $request->post('name');
            $newName = $request->post('new_name');
            if (!Template::get($gateway, $name)) {
                return json(['code' => 1, 'msg' => '模版不存在']);
            }
            if ($newName != $name) {
                Template::delete($gateway, [$name]);
            }
            $templateId = $request->post('template_id');
            $sign = $request->post('sign');
            Template::save($gateway, $newName, ['template_id' => $templateId, 'sign' => $sign]);
            return json(['code' => 0, 'msg' => 'ok']);
        }
        return view('template/update');
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function deleteTemplate(Request $request): Response
    {
        $gateway = $request->post('gateway');
        $names = (array)$request->post('name');
        Template::delete($gateway, $names);
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 获取模版
     * @param Request $request
     * @return Response
     */
    public function getTemplate(Request $request): Response
    {
        $gateway = $request->get('gateway');
        $name = $request->get('name');
        return json(['code' => 0, 'msg' => 'ok', 'data' => Template::get($gateway, $name)]);
    }

}
