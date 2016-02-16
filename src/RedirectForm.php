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

    /** @var Api FreeKassa API component */
    public $api;
    /** @var integer internal invoice ID */
    public $invoiceId;
    /** @var float amount to pay */
    public $amount;
    /** @var string description for payment system */
    public $description = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        assert(isset($this->api));
        assert(isset($this->invoiceId));
        assert(isset($this->amount));
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->view->registerJs("$('#free-kassa-checkout-form').submit();", View::POS_READY);

        return $this->render('redirect', [
            'message' => $this->message,
            'api' => $this->api,
            'invoiceId' => $this->invoiceId,
            'amount' => number_format($this->amount, 2, '.', ''),
            'description' => $this->description,
        ]);
    }
}