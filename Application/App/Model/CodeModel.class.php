<?php
namespace App\Model;
use Think\Model;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class CodeModel extends CommonModel{

	/*
	 * 获取验证码
	 */
	public function get_sms_code($phone_number, $template_id){

		AlibabaCloud::accessKeyClient(C('AccessKey'), C('AccessSecket'))
			->regionId('cn-hangzhou')
			->asDefaultClient();

		try {
			$result = AlibabaCloud::rpc()
				->product('Dysmsapi')
				// ->scheme('https') // https | http
				->version('2017-05-25')
				->action('SendSms')
				->method('POST')
				->host('dysmsapi.aliyuncs.com')
				->options([
					'query' => [
						'RegionId' => "cn-hangzhou",
						'PhoneNumbers' => $phone_number,
						'SignName' => C('SignName'),
						'TemplateCode' => $template_id,
					],
				])
				->request();
			print_r($result->toArray());
		} catch (ClientException $e) {
			echo $e->getErrorMessage() . PHP_EOL;
		} catch (ServerException $e) {
			echo $e->getErrorMessage() . PHP_EOL;
		}

	}
}