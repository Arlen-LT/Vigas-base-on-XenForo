<?php

namespace XF\Captcha;

use XF\Template\Templater;

class HCaptcha extends AbstractCaptcha
{
	/**
	 * hCaptcha site key
	 *
	 * @var string
	 */
	protected $siteKey;

	/**
	 * hCaptcha secret key
	 *
	 * @var string
	 */
	protected $secretKey;

	/**
	 * Enable hCaptcha invisible mode
	 *
	 * @var bool
	 */
	protected $invisibleMode = false;

	public function __construct(\XF\App $app)
	{
		parent::__construct($app);
		$extraKeys = $app->options()->extraCaptchaKeys;
		if (!empty($extraKeys['hCaptchaSiteKey']) && !empty($extraKeys['hCaptchaSecretKey']))
		{
			$this->siteKey = $extraKeys['hCaptchaSiteKey'];
			$this->secretKey = $extraKeys['hCaptchaSecretKey'];
		}
		if (!empty($extraKeys['hCaptchaInvisible']))
		{
			$this->invisibleMode = $extraKeys['hCaptchaInvisible'];
		}
	}

	public function renderInternal(Templater $templater)
	{
		if (!$this->siteKey)
		{
			return '';
		}

		return $templater->renderTemplate('public:captcha_hcaptcha', [
			'siteKey' => $this->siteKey,
			'invisible' => $this->invisibleMode && !$this->forceVisible
		]);
	}

	public function isValid()
	{
		if (!$this->siteKey || !$this->secretKey)
		{
			return true; // if not configured, always pass
		}

		$request = $this->app->request();

		$captchaResponse = $request->filter('h-captcha-response', 'str');
		if (!$captchaResponse)
		{
			return false;
		}

		try
		{
			$client = $this->app->http()->client();

			$response = \GuzzleHttp\json_decode($client->post('https://hcaptcha.com/siteverify',
				['form_params' => [
					'sitekey' => $this->siteKey,
					'secret' => $this->secretKey,
					'response' => $captchaResponse,
					'remoteip' => $request->getIp()
				]
				])->getBody()->getContents(), true);

			$this->setResponse($response);

			if (!empty($response['success']))
			{
				return true;
			}

			$logErrors = [];
			if (!empty($response['error-codes']) && is_array($response['error-codes']))
			{
				foreach ($response['error-codes'] AS $errorCode)
				{
					switch ($errorCode)
					{
						case 'missing-input-secret':
						case 'invalid-input-secret':
						case 'sitekey-secret-mismatch':
							$logErrors[] = $errorCode;
					}
				}

				if ($logErrors)
				{
					\XF::logError("hCaptcha configuration error: " . implode(', ', $logErrors));
				}
			}

			return false;
		}
		catch(\GuzzleHttp\Exception\RequestException $e)
		{
			// this is an exception with the underlying request, so let it go through
			\XF::logException($e, false, 'hCaptcha connection error: ');
			return true;
		}
	}

	public function getPrivacyPolicy()
	{
		return \XF::phrase('hcaptcha_privacy_policy');
	}
}