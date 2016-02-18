<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yarcode\freekassa;

use yii\base\Widget;
use yii\web\View;

/**
 * Class RedirectForm
 * @package yarcode\freekassa
 */
class RedirectForm extends Widget
{
    public $message = 'Now you will be redirected to the payment system.';

    /** @var string View file name / path, you can specify your own */
    public $viewFile = 'redirect';

    /** @var Merchant FreeKassa API component */
    public $api;
    /** @var integer internal invoice ID */
    public $invoiceId;
    /** @var float amount to pay */
    public $amount;
    /** @var string description for payment system */
    public $description = '';
    /** @var string Client email */
    public $email;
    /** @var string Default UI language for payment interface */
    public $language;
    /** @var integer Suggested currency */
    public $currency;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        assert(isset($this->api));
        assert(isset($this->invoiceId));
        assert(isset($this->amount));
        assert(isset($this->email));

        $this->language = $this->language ?: $this->api->defaultLanguage;
        $this->currency = $this->currency ?: $this->api->defaultCurrency;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render($this->viewFile, [
            'message' => $this->message,
            'api' => $this->api,
            'invoiceId' => $this->invoiceId,
            'amount' => number_format($this->amount, 2, '.', ''),
            'description' => $this->description,
            'language' => $this->language,
            'currency' => $this->currency,
            'email' => $this->email
        ]);
    }
}