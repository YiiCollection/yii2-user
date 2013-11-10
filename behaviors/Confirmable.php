<?php namespace dektrium\user\behaviors;

use yii\base\Behavior;
use yii\helpers\Security;
use dektrium\user\models\User;

/**
 * Confirmable is responsible to verify if an account is already confirmed to sign in, and to send emails with
 * confirmation instructions. Confirmation instructions are sent to the user email after creating a record and when
 * manually requested by a new confirmation instruction request.
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class Confirmable extends Behavior
{
    /**
     * @var bool
     */
    public $allowUnconfirmedLogin = false;

    /**
     * @var int
     */
    public $confirmWithin = 86400;

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			User::EVENT_BEFORE_REGISTER => 'generateConfirmationData',
			User::EVENT_AFTER_REGISTER  => 'sendConfirmationMessage'
		];
	}

	/**
	 * Generates confirmation data before saving the user.
	 */
	public function generateConfirmationData()
    {
        $this->owner->confirmation_token = Security::generateRandomKey();
        $this->owner->confirmation_sent_time = time();
    }

	/**
	 * Sends confirmation message to user's email address.
	 */
	public function sendConfirmationMessage()
	{
		/** @var \yii\mail\BaseMailer $mailer */
		$mailer = \Yii::$app->mail;
		$mailer->compose()
			->setTo($this->owner->email)
			->setFrom(\Yii::$app->params['adminEmail'])
			->setSubject('Account confirmation')
			->setHtmlBody(\Yii::$app->view->renderFile('@user/views/mail/confirmation.php', ['user' => $this->owner]))
			->send();
	}

	/**
	 * Confirms the user.
	 *
	 * @return bool
	 */
	public function confirm()
	{
		if ($this->getIsConfirmed()) {
			return true;
		}

		if ($this->owner->confirmation_sent_time + 86400 < time()) {
			return false;
		}

		$this->owner->confirmation_token = null;
		$this->owner->confirmation_sent_time = null;
		$this->owner->confirmation_time = time();

		return $this->owner->save(false);
	}

	/**
	 * @return bool Whether the user has been confirmed
	 */
	public function getIsConfirmed()
    {
		return $this->owner->confirmation_time !== null;
    }
}