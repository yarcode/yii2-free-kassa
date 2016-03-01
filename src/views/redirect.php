<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 *
 * @var \yii\web\View $this
 * @var \yarcode\freekassa\Merchant $api
 * @var integer $invoiceId
 * @var float $amount
 * @var string $description
 * @var string $email
 * @var string $language
 * @var string $currency
 */

$this->registerJs("$('#free-kassa-checkout-form').submit();", $this::POS_READY);

?>

<div class="free-kassa-checkout">
    <p><?= $message ?></p>
    <form id="free-kassa-checkout-form" action="http://www.free-kassa.ru/merchant/cash.php" method="GET">
        <?= \yii\helpers\Html::hiddenInput('m', $api->merchantId) ?>
        <?= \yii\helpers\Html::hiddenInput('oa', $amount) ?>
        <?= \yii\helpers\Html::hiddenInput('o', $invoiceId) ?>
        <?= \yii\helpers\Html::hiddenInput('s', $api->generateMerchantFormSign($amount, $invoiceId)) ?>
        <?php if(null !== $currency): ?>
            <?= \yii\helpers\Html::hiddenInput('i', $currency) ?>
        <?php endif ?>
        <?= \yii\helpers\Html::hiddenInput('em', $email) ?>
        <?= \yii\helpers\Html::hiddenInput('lang', $language) ?>
    </form>
</div>