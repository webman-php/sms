<?php

namespace plugin\sms\app\admin\controller;

use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Strategies\OrderStrategy;
use PHPMailer\PHPMailer\Exception;
use plugin\admin\app\model\Option;
use plugin\email\api\Email;
use plugin\email\api\Install;
use plugin\sms\api\Sms;
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
        $smsTemplate = config('plugin.sms.sms-template');
        return view('setting/index', [
            'template' => $smsTemplate,
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
        $smsTemplate = config('plugin.sms.sms-template');
        $config = Sms::getConfig();
        if (!$config) {
            $config = [
                'timeout' => 5.0,
                'default' => [
                    'strategy' => OrderStrategy::class,
                    'gateways' => [],
                ],
                'gateways' => [],
            ];
        }
        $gatewayName = $request->post('gateway');
        if (!isset($smsTemplate['gateways'][$gatewayName])) {
            return json(['code' => 1, 'msg' => '数据错误']);
        }
        $gateway = $smsTemplate['gateways'][$gatewayName];
        $gatewayConfig = [];
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
     * 短信测试
     * @param Request $request
     * @return Response
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     * @throws BusinessException
     */
    public function test(Request $request): Response
    {
        if ($request->method() === 'GET') {
            $gateway = $request->get('gateway');
            return view('setting/test', [
                'gateway' => $gateway,
            ]);
        }

        $gateway = $request->post('gateway');
        $to = $request->post('mobile');
        $data = $request->post('data');
        $data = $data ? json_decode($data, true) : [];
        try {
            Sms::send($to, [
                'content' => $request->post('content'),
                'template' => $request->post('template'),
                'data' => $data
            ], [$gateway]);
        }  catch (Throwable $e) {
            throw new BusinessException(current($e->getExceptions())->getMessage());
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
