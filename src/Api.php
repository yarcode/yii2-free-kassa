<?php
/**
 * @author Valentin Konusov <rlng-krks@yandex.ru>
 */

namespace yarcode\freekassa;

use GuzzleHttp\Client;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yiidreamteam\perfectmoney\events\GatewayEvent;

/**
 * Class Api
 * @package yarcode\freekassa
 */
class Api extends Component
{
    const BASE_API_URL = "http://www.free-kassa.ru/api.php";

    const CONFIRMATION_RESPONSE_TEXT = 'YES';

    const CURRENCY_MEGAFON_CENTRAL_BRANCH = "143";
    const CURRENCY_QIWI_RUB = "63";
    const CURRENCY_QIWI_USD = "123";
    const CURRENCY_YANDEX_MONEY = "45";
    const CURRENCY_DEBIT_CART_RUB = "94";
    const CURRENCY_DEBIT_CART_USD = "100";
    const CURRENCY_DEBIT_CART_EUR = "124";
    const CURRENCY_BITCOIN = "116";
    const CURRENCY_FK_WALLET_RUB = "133";
    const CURRENCY_OOOPAY_USD = "87";
    const CURRENCY_OOOPAY_RUR = "106";
    const CURRENCY_OOOPAY_EUR = "109";
    const CURRENCY_WMZ_BILL = "131";
    const CURRENCY_WMR_BILL = "130";
    const CURRENCY_WEB_MONEY_WMR = "1";
    const CURRENCY_WEB_MONEY_WMZ = "2";
    const CURRENCY_WEB_MONEY_WME = "3";
    const CURRENCY_TINKOFF_CREDIT_SYSTEMS = "112";
    const CURRENCY_W1_RUR = "74";
    const CURRENCY_PAYEER_RUB = "114";
    const CURRENCY_PAYEER_USD = "115";
    const CURRENCY_PERFECT_MONEY_USD = "64";
    const CURRENCY_PERFECT_MONEY_EUR = "69";
    const CURRENCY_OKPAY_USD = "62";
    const CURRENCY_OKPAY_RUB = "60";
    const CURRENCY_OKPAY_EUR = "61";
    const CURRENCY_Z_PAYMENT = "102";
    const CURRENCY_ALFA_BANK_RUR = "79";
    const CURRENCY_VTB24_RUR = "81";
    const CURRENCY_PROMSVYAZ_BANK = "110";
    const CURRENCY_RUSSKIJ_STANDART_BANK = "113";
    const CURRENCY_MOBILE_PAYMENT_MTS = "84";
    const CURRENCY_MOBILE_PAYMENT_BEELINE = "83";
    const CURRENCY_TERMINAL_RUSSIA = "99";
    const CURRENCY_SALON_SVYAZI = "118";
    const CURRENCY_MONEY_TRANSFER = "117";
    const CURRENCY_LAND_CREDIT = "96";
    const CURRENCY_MY_KASSA_RUR = "125";
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_SEVERO_ZAPAD = "137";
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_SIBERIA = "138";
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_KAVKAZ = "139";
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_POVOLOJIE = "140";
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_URAL = "141";
    const CURRENCY_MOBILE_PAYMENT_MEGAFON_DALNIJ_VOSTOK = "142";

    /** @var string Merchant ID */
    public $merchantId;

    /** @var string Default interface language, possible values - ru / en */
    public $defaultLanguage = 'ru';

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


    /** @var string Account password */
    public $accountPassword;

    /** @var string Wallet number (e.g. U123456) */
    public $walletNumber;

    /** @var string Wallet currency (e.g. USD) */
    public $walletCurrency = 'USD';

    /** @var string Secret string from the PM settings page */
    public $alternateSecret;

    /** @var string Merchant name to display in payment form */
    public $merchantName;

    protected $hash;

    public $resultUrl;
    public $successUrl;
    public $failureUrl;

    protected $httpClient;

    public function init()
    {
        assert(isset($this->accountId));
        assert(isset($this->accountPassword));
        assert(isset($this->walletNumber));
        assert(isset($this->alternateSecret));
        assert(isset($this->merchantName));

        $this->resultUrl = Url::to($this->resultUrl, true);
        $this->successUrl = Url::to($this->successUrl, true);
        $this->failureUrl = Url::to($this->failureUrl, true);

        $this->hash = strtoupper(md5($this->alternateSecret));
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
     * @return Client
     */
    public function getHttpClient()
    {
        if(null === $this->httpClient) {
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
     * Transfers money to another account
     *
     * @param string $target Target wallet
     * @param float $amount
     * @param string|null $paymentId
     * @param string|null $memo
     *
     * @return array|false
     */
    public function transfer($target, $amount, $paymentId = null, $memo = null)
    {
        $params = [
            'Payer_Account' => $this->walletNumber,
            'Payee_Account' => $target,
            'Amount' => $amount,
            'PAY_IN' => 1,
        ];

        if (strlen($paymentId)) {
            $params['PAYMENT_ID'] = $paymentId;
        }

        if (strlen($memo)) {
            $params['Memo'] = $memo;
        }

        return $this->call('confirm', $params);
    }

    /**
     * Performs api call
     *
     * @param string $script Api script name
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

        $httpParams = http_build_query(ArrayHelper::merge($defaults, $params));
        $scriptUrl = "http://www.free-kassa.ru/api.php?{$httpParams}";

        $queryResult = @file_get_contents($scriptUrl);

        if ($queryResult === false) {
            return false;
        }

        if (!preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $queryResult, $items, PREG_SET_ORDER)) {
            return false;
        }

        $result = [];
        foreach ($items as $item) {
            $result[$item[1]] = $item[2];
        }

        return $result;
    }

    /**
     * Get account wallet balance
     *
     * @return array|bool
     */
    public function balance()
    {
        return $this->call('balance');
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