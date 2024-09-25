<?php

namespace Debva\Nix;

class Telegram
{
    const PARSE_MODE_HTML = 'HTML';

    const PARSE_MODE_MARKDOWN = 'MarkdownV2';

    protected $token;

    protected $url = 'https://api.telegram.org/bot';

    protected $fileUrl = 'https://api.telegram.org/file/bot';

    protected $endpoint;

    protected $parameter;

    public function __construct($token = null)
    {
        $this->token = $token ? $token : env('TELEGRAM_TOKEN', '');
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

    public function inlineKeyboard(...$keyboard)
    {
        return json_encode(["inline_keyboard" => $keyboard]);
    }

    public function listenWebhook(\Closure $closure)
    {
        $listen = json_decode(file_get_contents('php://input'), true);
        if (!is_null($listen) && count($listen) === 2) {
            $key = array_keys($listen)[1];
            $data = $listen[$key];
            return $closure($data, $key);
        }
        return false;
    }

    public function authorize($authData, $exp = 86400)
    {
        if (!isset($authData['hash']) || !isset($authData['auth_date'])) {
            throw new \Exception('Invalid telegram request!', 400);
        }

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
            throw new \Exception('Authentication telegram data is no longer valid', 401);
        }

        return $authData;
    }

    public function getFileUrl($fileId, $path = null)
    {
        $path = $path ? $path : $this->getFile(['file_id' => $fileId]);
        return isset($path['result']['file_path']) ? "{$this->fileUrl}{$this->token}/{$path['result']['file_path']}" : null;
    }

    protected function send()
    {
        try {
            $ch = curl_init();

            if (in_array($this->endpoint, ['sendDocument', 'sendPhoto'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
            }

            curl_setopt($ch, CURLOPT_URL, "{$this->url}/{$this->endpoint}");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \Exception('Error: ' . curl_error($ch), 500);
            }

            return json_decode($response, true);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    protected function endpointList()
    {
        return [
            'setWebhook',
            'getWebhookInfo',
            'sendMessage',
            'editMessageText',
            'deleteMessage',
            'sendDocument',
            'sendPhoto',
            'getUserProfilePhotos',
            'getFile'
        ];
    }
}
