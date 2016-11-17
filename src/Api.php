<?php
/**
 * @author Valentin Konusov <rlng-krks@yandex.ru>
 */

namespace yarcode\freekassa;

use GuzzleHttp\Client;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class Api
 * @package yarcode\freekassa
 */
class Api extends Component
{
    const BASE_API_URL = "https://wallet.free-kassa.ru/api_v1.php";

    /** @var string Merchant ID */
    public $merchantId;

    /** @var string Wallet ID, used for API calls: balance, transfer */
    public $walletId;

    /** @var string API secret key */
    public $apiKey;

    protected $httpClient;

    public function init()
    {
        assert(isset($this->merchantId));
        assert(isset($this->walletId));
        assert(isset($this->apiKey));
    }

    /**
     * @return Client GuzzleHttp client
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Client([
                'base_uri' => static::BASE_API_URL
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Performs api call
     *
     * @param string $action Api script name
     * @param array $params Request parameters
     * @return array|bool
     */
    protected function call($action, $params = [])
    {
        $defaults = [
            'wallet_id' => $this->walletId,
            'action' => $action
        ];

        $client = $this->getHttpClient();

        $result = $client->post(null, [
            'form_params' => ArrayHelper::merge($defaults, $params)
        ]);

        return Json::decode($result->getBody());
    }

    /**
     * @return array|bool
     */
    public function balance()
    {
        $data = [
            'sign' => md5(implode('', [$this->walletId, $this->apiKey])),
        ];

        return ArrayHelper::getValue($this->call('get_balance', $data), 'data', null);
    }

    /**
     * @param $wallet
     * @param $amount
     * @param $description
     * @param $currency
     * @param $sign
     * @return array|bool
     */
    public function withdraw($wallet, $amount, $description, $currency, $sign)
    {
        $data = [
            'purse' => $wallet,
            'amount' => $amount,
            'desc' => $description,
            'currency' => $currency,
            'sign' => md5(implode('',
                [$this->walletId, $currency, static::formatAmount($amount), $wallet, $this->apiKey]))
        ];

        return $this->call('cashout', ['data' => $data]);
    }

    /**
     * Format amount to correct format
     * to prevent errors
     * @param float $amount
     * @return float
     */
    protected static function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
