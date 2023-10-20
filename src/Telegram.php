<?php

namespace Debva\Nix;

class Telegram
{
    const PARSE_MODE_HTML = 'HTML';

    const PARSE_MODE_MARKDOWN = 'MarkdownV2';

    protected $url = 'https://api.telegram.org/bot';

    protected $fileUrl = 'https://api.telegram.org/file/bot';

    protected $endpoint;

    protected $parameter;

    public function __construct($token = null)
    {
        $this->token = $token ? $token : env('TELEGRAM_TOKEN');
        $this->url = "{$this->url}{$this->token}";
    }

    public function __call($endpoint, $parameter)
    {
        try {
            if (in_array($endpoint, $this->endpointList())) {
                $this->endpoint = $endpoint;
                $this->parameter = reset($parameter);
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
        if (!is_null($listen) and isset($listen['message'])) {
            return $closure($listen['message']);
        }
        return false;
    }

    public function authorize($authData, $exp = 86400)
    {
        $dataCheckArr = [];
        $checkHash = $authData['hash'];

        unset($authData['hash']);
        foreach ($authData as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }

        sort($dataCheckArr);
        $dataCheckStr = implode("\n", $dataCheckArr);
        $secretKey = hash('sha256', $this->token, true);
        $hash = hash_hmac('sha256', $dataCheckStr, $secretKey);

        if (strcmp($hash, $checkHash) !== 0) {
            throw new \Exception('Authentication telegram not valid', 400);
        }

        if ((time() - $authData['auth_date']) > $exp) {
            throw new \Exception('Authentication data is no longer valid', 401);
        }

        return $authData;
    }

    public function getFileUrl($fileId)
    {
        $path = $this->getFile(['file_id' => $fileId]);
        return isset($path->result->file_path) ? "{$this->fileUrl}{$this->token}/{$path->result->file_path}" : null;
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

            'getUserProfilePhotos',
            'getFile'

        ];
    }
}
