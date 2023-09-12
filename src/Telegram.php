<?php

namespace Debva\Nix;

class Telegram
{
    const PARSE_MODE_HTML = 'HTML';

    const PARSE_MODE_MARKDOWN = 'MarkdownV2';

    protected $url = 'https://api.telegram.org/bot';

    protected $endpoint;

    protected $parameter;

    public function __construct($token)
    {
        $this->url = "{$this->url}{$token}";
    }

    public function __call($endpoint, $parameter)
    {
        try {
            if (in_array($endpoint, $this->endpointList())) {
                $this->endpoint = $endpoint;
                $this->parameter = end($parameter);
                return $this->send();
            }

            throw new \Exception('Endpoint not found');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function inlineKeyboardButton($keyboard)
    {
        return json_encode(["inline_keyboard" => [$keyboard]]);
    }

    public function listenWebhook(\Closure $closure)
    {
        $listen = json_decode(file_get_contents('php://input'), true);
        if (!is_null($listen) and isset($listen['callback_query'])) {
            return $closure($listen['callback_query']['message']['message_id'], $listen['callback_query']);
        }
    }

    protected function send()
    {
        try {
            $response = file_get_contents("{$this->url}/{$this->endpoint}", false, stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'content' => http_build_query($this->parameter),
                    'header'  => "Content-type: application/x-www-form-urlencoded"
                ]
            ]));
            
            return json_decode($response);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    protected function endpointList()
    {
        return [
            'setWebhook',
            'sendMessage',
            'editMessageText',
        ];
    }
}
