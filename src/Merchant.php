<?php
/**
 * @author Valentin Konusov <rlng-krks@yandex.ru>
 */

namespace yarcode\freekassa;

use GuzzleHttp\Client;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yiidreamteam\perfectmoney\events\GatewayEvent;

/**
 * Class Merchant
 * @package yarcode\freekassa
 */
class Merchant extends Component
{
    const BASE_API_URL = "http://www.free-kassa.ru/api.php";

    const CONFIRMATION_RESPONSE_TEXT = 'YES';

    const CURRENCY_MEGAFON_CENTRAL_BRANCH = 143;
    const CURRENCY_QIWI_RUB = 63;
    const CURRENCY_QIWI_USD = 123;
    const CURRENCY_YANDEX_MONEY = 45;
    const CURRENCY_DEBIT_CART_RUB = 94;
    const CURRENCY_DEBIT_CART_USD = 100;
    const CURRENCY_DEBIT_CART_EUR = 124;
    const CURRENCY_BITCOIN = 116;
    const CURRENCY_FK_WALLET_RUB = 133;
    const CURRENCY_OOOPAY_USD = 87;
    const CURRENCY_OOOPAY_RUR = 106;
    const CURRENCY_OOOPAY_EUR = 109;
    const CURRENCY_WMZ_BILL = 131;
    const CURRENCY_WMR_BILL = 130;
    const CURRENCY_WEB_MONEY_WMR = 1;
    const CURRENCY_WEB_MONEY_WMZ = 2;
    const CURRENCY_WEB_MONEY_WME = 3;
    const CURRENCY_TINKOFF_CREDIT_SYSTEMS = 112;
    const CURRENCY_W1_RUR = 74;
    const CURRENCY_PAYEER_RUB = 114;
    const CURRENCY_PAYEER_USD = 115;
    const CURRENCY_PERFECT_MONEY_USD = 64;
    const CURRENCY_PERFECT_MONEY_EUR = 69;
    const CURRENCY_OKPAY_USD = 62;
    const CURRENCY_OKPAY_RUB = 60;
    const CURRENCY_OKPAY_EUR = 61;
    const CURRENCY_Z_PAYMENT = 102;
    const CURRENCY_ALFA_BANK_RUR = 79;
    const CURRENCY_VTB24_RUR = 81;
    const CURRENCY_PROMSVYAZ_BANK = 110;
    const CURRENCY_RUSSKIJ_STANDART_BANK = 113;
    const CURRENCY_MOBILE_PAYMENT_MTS = 84;
    const CURRENCY_MOBILE_PAYMENT_BEELINE = 83;
    const CURRENCY_TERMINAL_RUSSIA = 99;
    const CURRENCY_SALON_SVYAZI = 118;
    const CURRENCY_MONEY_TRANSFER = 117;
    const CURRENCY_LAND_CREDIT = 96;
    const CURRENCY_MY_KASSA_RUR = 125;
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_SEVERO_ZAPAD = 137;
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_SIBERIA = 138;
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_KAVKAZ = 139;
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_POVOLOJIE = 140;
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_URAL = 141;
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_DALNIJ_VOSTOK = 142;

    /** @var string Merchant ID */
    public $merchantId;

    /** @var string Default interface language, possible values - ru / en */
    public $defaultLanguage = 'ru';
    
    /**
     * @var integer Default suggested currencyID, use only FreeKassa IDs
     */
    public $defaultCurrency;
    
    /**
     * Array of Free Kassa servers IPs
     * Set to null, if you want to prevent IP check
     * @var array
     */
    public $systemIPs = ['136.243.38.147', '136.243.38.149', '136.243.38.150', '136.243.38.151', '136.243.38.189'];

    /** @var string used for creating the hash to send merchant form (in API doc see 'secret1') */
    public $merchantFormSecret;

    /** @var string used for creating the hash to check the data (in API doc see 'secret2') */
    public $checkDataSecret;

    /** @var Client */
    protected $httpClient;

    /**
     * @inheritdoc
     */
    public function init()
    {
        assert(isset($this->merchantId));
        assert(isset($this->merchantFormSecret));
        assert(isset($this->checkDataSecret));
    }

    /**
     * @return array list of possible payments system currencies
     */
    public static function getCurrencies()
    {
        return [
            static::CURRENCY_MEGAFON_CENTRAL_BRANCH => "Мобильный Платеж МегаФон Центральный филиал",
            static::CURRENCY_QIWI_RUB => "QIWI кошелек",
            static::CURRENCY_QIWI_USD => "QIWI кошелек USD",
            static::CURRENCY_YANDEX_MONEY => "Яндекс.Деньги",
            static::CURRENCY_DEBIT_CART_RUB => "VISA/MASTERCARD RUB",
            static::CURRENCY_DEBIT_CART_USD => "VISA/MASTERCARD USD",
            static::CURRENCY_DEBIT_CART_EUR => "VISA/MASTERCARD EUR",
            static::CURRENCY_BITCOIN => "Bitcoin",
            static::CURRENCY_FK_WALLET_RUB => "FK WALLET RUB",
            static::CURRENCY_OOOPAY_USD => "OOOPAY USD",
            static::CURRENCY_OOOPAY_RUR => "OOOPAY RUR",
            static::CURRENCY_OOOPAY_EUR => "OOOPAY EUR",
            static::CURRENCY_WMZ_BILL => "WMZ-bill",
            static::CURRENCY_WMR_BILL => "WMR-bill",
            static::CURRENCY_WEB_MONEY_WMR => "WebMoney WMR",
            static::CURRENCY_WEB_MONEY_WMZ => "WebMoney WMZ",
            static::CURRENCY_WEB_MONEY_WME => "WebMoney WME",
            static::CURRENCY_TINKOFF_CREDIT_SYSTEMS => "Тинькофф Кредитные Системы",
            static::CURRENCY_W1_RUR => "W1 RUR",
            static::CURRENCY_PAYEER_RUB => "PAYEER RUB",
            static::CURRENCY_PAYEER_USD => "PAYEER USD",
            static::CURRENCY_PERFECT_MONEY_USD => "Perfect Money USD",
            static::CURRENCY_PERFECT_MONEY_EUR => "Perfect Money EUR",
            static::CURRENCY_OKPAY_USD => "OKPAY USD",
            static::CURRENCY_OKPAY_RUB => "OKPAY RUB",
            static::CURRENCY_OKPAY_EUR => "OKPAY EUR",
            static::CURRENCY_Z_PAYMENT => "Z-Payment",
            static::CURRENCY_ALFA_BANK_RUR => "Альфа-банк RUR",
            static::CURRENCY_VTB24_RUR => "ВТБ24 RUR",
            static::CURRENCY_PROMSVYAZ_BANK => "Промсвязьбанк",
            static::CURRENCY_RUSSKIJ_STANDART_BANK => "Русский стандарт",
            static::CURRENCY_MOBILE_PAYMENT_MTS => "Мобильный Платеж МТС",
            static::CURRENCY_MOBILE_PAYMENT_BEELINE => "Мобильный Платеж Билайн",
            static::CURRENCY_TERMINAL_RUSSIA => "Терминалы России",
            static::CURRENCY_SALON_SVYAZI => "Салоны связи",
            static::CURRENCY_MONEY_TRANSFER => "Денежные переводы",
            static::CURRENCY_LAND_CREDIT => "LendСredit.ru",
            static::CURRENCY_MY_KASSA_RUR => "Mykassa RUR",
            static::CURRENCY_MOBILE_PAYMENT_MEGAFON_SEVERO_ZAPAD => "Мобильный Платеж МегаФон Северо-Западный филиал",
            static::CURRENCY_MOBILE_PAYMENT_MEGAFON_SIBERIA => "Мобильный Платеж МегаФон Сибирский филиал",
            static::CURRENCY_MOBILE_PAYMENT_MEGAFON_KAVKAZ => "Мобильный Платеж МегаФон Кавказский филиал",
            static::CURRENCY_MOBILE_PAYMENT_MEGAFON_POVOLOJIE => "Мобильный Платеж МегаФон Поволжский филиал",
            static::CURRENCY_MOBILE_PAYMENT_MEGAFON_URAL => "Мобильный Платеж МегаФон Уральский филиал",
            static::CURRENCY_MOBILE_PAYMENT_MEGAFON_DALNIJ_VOSTOK => "Мобильный Платеж МегаФон Дальневосточный филиал",
        ];
    }

    /**
     * @return Client GuzzleHttp client
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Client([
                'base_url' => static::BASE_API_URL
            ]);
        }

        return $this->httpClient;
    }

    /**
     * @param $value
     * @param null $default
     * @return string currency label
     */
    public static function getCurrencyLabel($value, $default = null)
    {
        return ArrayHelper::getValue(static::getCurrencies(), $value, $default);
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
            'merchant_id' => $this->merchantId,
            's' => $this->generateApiCallHash(),
            'action' => $action
        ];

        $client = $this->getHttpClient();

        $result = $client->get(null, [
            'query' => ArrayHelper::merge($defaults, $params)
        ]);

        return static::xmlToArray($result->xml());
    }

    /**
     * Convert \SimpleXMLElement to array
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    protected static function xmlToArray(\SimpleXMLElement $xml)
    {
        return Json::decode(Json::encode($xml));
    }

    /**
     * Get account wallet balance
     * For more info see: http://www.free-kassa.ru/docs/api.php#balance
     *
     * @return float
     */
    public function balance()
    {
        return ArrayHelper::getValue($this->call('get_balance'), 'balance', 0);
    }

    /**
     * Get order info
     * For more info see: http://www.free-kassa.ru/docs/api.php#check_order_status
     * Return attributes: status, intid, id, date, amount, description, email
     *
     * @param integer|string $orderId Internal order/invoice ID
     * @return array|bool Array with keys: status, intid, id, date, amount, description, email
     */
    public function getOrderInfo($orderId)
    {
        return $this->call('check_order_status', ['order_id' => $orderId]);
    }

    /**
     * Withdraw money to one of specified in merchant admin section wallets
     * For more info see: http://www.free-kassa.ru/docs/api.php#api_payment
     *
     * @param integer $walletType
     * Possible values: ooopay, yandex, qiwi, payeer, card (VISA/MASTERCARD),
     * wmr, wmz, w1, fkw (Free-kassa Wallet)
     * @param float $amount
     * @return array|bool Array with keys: desc, PaymentId
     */
    public function withdraw($walletType, $amount)
    {
        return $this->call('payment', [
            'currency' => $walletType,
            'amount' => static::formatAmount($amount)
        ]);
    }

    /**
     * Create Invoice to user with specified email
     * For more info see: http://www.free-kassa.ru/docs/api.php#create_bill
     *
     * @param $email
     * @param $amount
     * @param $desc
     * @return array|bool
     */
    public function createInvoice($email, $amount, $desc)
    {
        return ArrayHelper::getValue($this->call('create_bill', [
            'email' => $email,
            'amount' => static::formatAmount($amount),
            'desc' => urlencode($desc)
        ]), 'desc');
    }

    /**
     * @param array $data
     * @return bool
     * @throws HttpException
     * @throws \yii\db\Exception
     */
    public function processResult($data)
    {
        if (null !== $this->systemIPs && !$this->checkIp(\Yii::$app->request->getUserIP())) {
            throw new ForbiddenHttpException("Not allowed IP address");
        }

        if (!$this->checkHash($data)) {
            throw new ForbiddenHttpException('Hash error');
        }

        $event = new GatewayEvent(['gatewayData' => $data]);

        $this->trigger(GatewayEvent::EVENT_PAYMENT_REQUEST, $event);
        if (!$event->handled) {
            throw new HttpException(503, 'Error processing request');
        }

        $transaction = \Yii::$app->getDb()->beginTransaction();
        try {
            $this->trigger(GatewayEvent::EVENT_PAYMENT_SUCCESS, $event);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            \Yii::error('Payment processing error: ' . $e->getMessage(), 'FreeKassa');
            throw new HttpException(503, 'Error processing request');
        }

        return true;
    }

    /**
     * Return API call hash
     * @return string
     */
    protected function generateApiCallHash()
    {
        return md5(implode('', [$this->merchantId, $this->checkDataSecret]));
    }

    /**
     * Generate merchant form hash
     * @param $amount
     * @param $invoiceId
     * @return string
     */
    public function generateMerchantFormSign($amount, $invoiceId)
    {
        return md5(implode(':',
            [$this->merchantId, static::formatAmount($amount), $this->merchantFormSecret, $invoiceId]));
    }

    /**
     * Return result of checking IP address
     * Used for merchant payment notifications checking
     * @param $ip
     * @return bool
     */
    public function checkIp($ip)
    {
        return (in_array($ip, $this->systemIPs));
    }

    /**
     * Return result of checking SCI hash
     *
     * @param array $data Request array to check, usually $_POST
     * @return bool
     */
    public function checkHash($data)
    {
        if (!isset(
            $data['MERCHANT_ID'],
            $data['AMOUNT'],
            $data['intid'],
            $data['MERCHANT_ORDER_ID'],
            $data['P_EMAIL'],
            $data['CUR_ID'],
            $data['SIGN'])
        ) {
            return false;
        }

        $params = [
            $this->merchantId,
            static::formatAmount($data['AMOUNT']),
            $this->checkDataSecret,
            $data['MERCHANT_ORDER_ID']
        ];

        $hash = md5(implode(':', $params));

        if ($hash == $data['SIGN']) {
            return true;
        }

        \Yii::error('Hash check failed: ' . VarDumper::dumpAsString($params), 'FreeKassa');
        return false;
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