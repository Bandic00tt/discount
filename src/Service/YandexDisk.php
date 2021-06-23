<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class YandexDisk
{
    public const REMOTE_DIR = 'projects/discount/';
    private const ROOT_URL = 'https://cloud-api.yandex.net/v1/disk/resources';
    public const GET_DOWNLOAD_LINK = '/download';

    private Client $client;
    private string $token;
    private string $fileName;

    public function __construct(string $token, string $fileName)
    {
        $this->client = new Client(['timeout' => 30]);
        $this->token = $token;
        $this->fileName = $fileName;
    }

    /**
     * @return string|null
     * @throws GuzzleException
     */
    public function downloadFile(): ?string
    {
        $downloadLink = $this->getLink(self::GET_DOWNLOAD_LINK);

        return file_get_contents($downloadLink);
    }

    /**
     * @param string $fileName
     * @return string
     * @throws GuzzleException
     */
    public function deleteFile(string $fileName): string
    {
        $res = $this->client->request('DELETE', self::ROOT_URL, [
            'headers' => [
                'Authorization' => 'OAuth '. $this->token,
            ],
            'query' => [
                'path' => self::REMOTE_DIR . $fileName,
            ]
        ]);

        return (string)$res->getStatusCode();
    }

    /**
     * @param string $apiLink
     * @param string $method
     * @return string
     * @throws GuzzleException
     */
    public function getLink(string $apiLink, string $method = 'GET'): string
    {
        $getLinkUrl = self::ROOT_URL . $apiLink;

        $res = $this->client->request($method, $getLinkUrl, [
            'headers' => [
                'Authorization' => 'OAuth '. $this->token,
            ],
            'query' => [
                'path' => self::REMOTE_DIR . $this->fileName,
            ]
        ]);

        $data = json_decode($res->getBody()->getContents(), true);

        return $data['href'];
    }
}