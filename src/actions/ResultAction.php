<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yarcode\freekassa\actions;

use yarcode\freekassa\Api;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Class ResultAction
 * @package yarcode\freekassa\actions
 */
class ResultAction extends Action
{
    public $componentName;

    public $redirectUrl;

    public $sendConfirmationResponse = true;

    public $silent = false;

    /** @var Api */
    private $api;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->api = \Yii::$app->get($this->componentName);

        if (!$this->api instanceof Api) {
            throw new InvalidConfigException('Invalid FreeKassa component configuration');
        }

        parent::init();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function run()
    {
        try {
            $this->api->processResult(\Yii::$app->request->post());
        } catch (\Exception $e) {
            if (!$this->silent) {
                throw $e;
            }
        }

        if ($this->sendConfirmationResponse === true) {
            \Yii::$app->response->format = Response::FORMAT_RAW;
            return Api::CONFIRMATION_RESPONSE_TEXT;
        }

        if (isset($this->redirectUrl)) {
            return \Yii::$app->response->redirect($this->redirectUrl);
        }
    }
}