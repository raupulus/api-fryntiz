<?php

namespace App\Models\WebHooks;

use function config;

/**
 * Class GitlabWebhook
 *
 * @package App\Models\WebHooks
 */
class GitlabWebhook extends SimpleWebhookModel
{
    ## Token que viene de GitLab
    public $token;

    ## Request Completa
    public $request;

    ## Token local extraido del .env
    public $localToken;

    protected $fillable = [
        'token',
        'request',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->localToken = config('app.gitlab_token_deploy_api');
    }

    /**
     * Comprueba si el hash local y el remoto para devolver si es válido.
     */
    public function isValidHash()
    {
        if (! $this->token || ! $this->localToken) {
            return false;
        }

        if ($this->token === $this->localToken) {
            return true;
        }

        return false;
    }
}
