<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 *
 * @var \yii\web\View $this
 * @var \yarcode\freekassa\Merchant $api
 * @var integer $invoiceId
 * @var float $amount
 * @var string $description
 */
?>

<div class="free-kassa-checkout">
    <p><?= $message ?></p>
    <form id="free-kassa-checkout-form" action="http://www.free-kassa.ru/merchant/cash.php" method="GET">
        <?= \yii\helpers\Html::hiddenInput('m', 'ID MAGAZA') ?>
        <?= \yii\helpers\Html::hiddenInput('oa', 'SUMMA') ?>
        <?= \yii\helpers\Html::hiddenInput('o', 'INVOICE ID') ?>
        <?= \yii\helpers\Html::hiddenInput('s', 'HASH') ?>
        <?= \yii\helpers\Html::hiddenInput('i', ' CURRENCY') ?>
        <?= \yii\helpers\Html::hiddenInput('em', 'EMAIL PLATELSHIKA') ?>
        <?= \yii\helpers\Html::hiddenInput('lang', 'Language') ?>
        <?= \yii\helpers\Html::hiddenInput('us_key1', 'Additional keys') ?>
        <?= \yii\helpers\Html::hiddenInput('us_key2', 'Additional keys') ?>

        <input type="hidden" name="PAYEE_ACCOUNT" value="<?= $api->walletNumber ?>">
        <input type="hidden" name="PAYEE_NAME" value="<?= $api->merchantName ?>">
        <input type="hidden" name="PAYMENT_UNITS" value="<?= $api->walletCurrency ?>">
        <input type="hidden" name="STATUS_URL" value="<?= $api->resultUrl ?>">
        <input type="hidden" name="PAYMENT_URL" value="<?= $api->successUrl ?>">
        <input type="hidden" name="NOPAYMENT_URL" value="<?= $api->failureUrl ?>">
        <input type="hidden" name="NOPAYMENT_URL_METHOD" value="POST">
        <input type="hidden" name="PAYMENT_URL_METHOD" value="POST">
        <input type="hidden" name="PAYMENT_ID" value="<?= $invoiceId ?>">
        <input type="hidden" name="PAYMENT_AMOUNT" value="<?= $amount ?>">
        <input type="hidden" name="SUGGESTED_MEMO" value="<?= $description ?>">
    </form>
</div>