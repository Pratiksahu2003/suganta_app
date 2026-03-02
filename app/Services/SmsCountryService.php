<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsCountryService
{
    protected $authKey;
    protected $authToken;
    protected $senderId;
    protected $url;
    protected $templates;

    public function __construct()
    {
        $this->authKey = config('services.smscountry.auth_key');
        $this->authToken = config('services.smscountry.auth_token');
        $this->senderId = config('services.smscountry.sender_id');
        $this->url = config('services.smscountry.url');
        $this->templates = config('sms_templates.templates', []);
    }

    /**
     * Send an SMS using a template.
     *
     * @param string $mobile
     * @param string $templateKey
     * @param array $variables
     * @return array
     * @throws Exception
     */
    public function sendTemplate($mobile, $templateKey, array $variables = [])
    {
        if (!isset($this->templates[$templateKey])) {
            throw new Exception("SMS template '{$templateKey}' not found.");
        }

        $template = $this->templates[$templateKey];
        $message = $template['content'];

        // Merge defaults
        if (config('sms_templates.settings.auto_replace_defaults', true)) {
            $defaults = config('sms_templates.defaults', []);
            $variables = array_merge($defaults, $variables);
        }

        // Validate required variables
        if (isset($template['required_variables'])) {
            foreach ($template['required_variables'] as $requiredVar) {
                if (!array_key_exists($requiredVar, $variables)) {
                    throw new Exception("Missing required variable '{$requiredVar}' for template '{$templateKey}'.");
                }
            }
        }

        // Replace variables in the template content
        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }

        return $this->sendRaw($mobile, $message, $template['dlt_template_id'] ?? null);
    }

    /**
     * Send a raw SMS message via SMS Country REST API.
     *
     * @param string $mobile
     * @param string $message
     * @param string|null $dltTemplateId
     * @return array
     */
    public function sendRaw($mobile, $message, $dltTemplateId = null)
    {
        if (empty($this->authKey) || empty($this->authToken) || empty($this->url)) {
            Log::warning('SMS Country credentials are missing. SMS not sent.', [
                'mobile' => $mobile,
                'message' => $message
            ]);
            return ['status' => false, 'message' => 'SMS credentials missing'];
        }

        try {
            // Basic Auth with AuthKey and AuthToken
            $response = Http::withBasicAuth($this->authKey, $this->authToken)
                ->post($this->url, [
                    'Text' => $message,
                    'Number' => $mobile,
                    'SenderId' => $this->senderId,
                    'DRNotifyUrl' => '', // Optional
                    'DRNotifyHttpMethod' => 'POST', // Optional
                    'Tool' => 'API', // Optional
                ]);

            $result = $response->json();
            $success = $response->successful();

            if (config('sms_templates.settings.log_template_usage', true)) {
                Log::info('SMS Sent via SMS Country', [
                    'mobile' => $mobile,
                    'message' => $message,
                    'response' => $result,
                    'dlt_template_id' => $dltTemplateId,
                    'status' => $response->status(),
                ]);
            }

            return ['status' => $success, 'response' => $result];
        } catch (Exception $e) {
            Log::error('SMS Country Error: ' . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }
    
}
