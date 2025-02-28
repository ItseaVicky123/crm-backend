<?php

namespace App\Lib\Tokenization;

use App\Models\User;
use App\Lib\KVS\UserAuthCodeKeyValuePair as AuthCodeStore;
use Carbon\Carbon;
use crm_notification;

class UserAuthCode
{
    private $user;
    private $code;
    private $expires_at;

    public function __construct(User $user = null)
    {
        if ($user) {
            $this->user = $user;
        }
    }

    /**
     * @param User $user
     * @return UserAuthCode
     */
    public static function make(User $user)
    {
        return new static($user);
    }

    /**
     * @param string $code
     * @return UserAuthCode
     */
    public static function loadCode(string $code)
    {
        return (new static)->set('code', $code);
    }

    /**
    * @return $this
    */
    public function store()
    {
        $store = AuthCodeStore::make()
           ->setEx($this->user->id);

        $this->code      = $store->key();
        $this->expires_at = $store->getExpiry();

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function get()
    {
        if (! $this->code) {
            throw new \Exception('Missing code');
        } elseif (! ($user_id = AuthCodeStore::make($this->code)->get())) {
            throw new \Exception('Invalid code');
        }

        $this->user = User::findOrFail($user_id);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function consume()
    {
        if (! $this->code) {
            throw new \Exception('Missing code');
        }

       AuthCodeStore::make($this->code)->del();
    }

    /**
     * @param $prop
     * @param $value
     * @return $this
     * @throws \Exception
     */
    public function set($prop, $value)
    {
        if (! property_exists($this, $prop)) {
            throw new \Exception("Unknown property '{$prop}'");
        }

        $this->$prop = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Carbon|null
     */
    public function getExpiry()
    {
        return $this->expires_at;
    }

    /**
     * @return User
     */
    public function user()
    {
        return $this->card;
    }

    public function sendCode($channel)
    {
        if (! $this->user) {
            throw new \Exception("No user attached to this code.");
        }

        if (! $this->code) {
            $this->store();
        }

        $company_name = \current_domain::company_name();
        $expiration   = AuthCodeStore::EXPIRES/60;

        if($channel == 'sms') {
            (new \providers\text\system($this->user->phone_number))->send_message("{$company_name}: Your security code is: {$this->code}. Your code expires in {$expiration} minutes. Please don't reply.");
        } else {
            $support_email = \current_domain::email('support');
            $emailBody     = <<<HTML
<p style="text-align:center">
	   <span style="font-family:verdana,geneva;font-size:x-small">
	      <span style="font-size:x-large">We've received your login request.</span>
	   </span>
	   <span style="font-family:verdana,geneva;font-size:x-small">
	      &nbsp;
	      <br>
	      <br>
	      <span style="font-size:large">Use the following security code to verify your identity and sign in to your account. This code will only be valid for {$expiration} minutes.&nbsp;</span>
	   </span>
	</p>
    <center>
	   <strong>
	       <span style="font-family:verdana,geneva;font-size:large">Your one-time security code: </span>
	   </strong>
	   <br>
	   <strong>
	      <span style="font-size:large">
		     <span style="font-family:verdana,geneva">{$this->code}</span>
			 <span style="font-family:verdana,geneva">&nbsp;</span>
		  </span>
	   </strong>
	</center>
    <p style="text-align:center">
	   <span style="font-family:verdana,geneva;font-size:small">If you didn't make this request, contact us immediately at (800) 455-9645 or email <strong>{$support_email}.</strong></span>
	</p>
HTML;
            $notifier  = new crm_notification();
            $recipient = $this->user->email;
            $full_name = $this->user->name;

            if (! $notifier->send_notification($full_name, $recipient, "Your {$company_name} Verification Code", $emailBody)) {
                throw new \Exception("There was a problem sending the email.");
            }
        }
    }
}
