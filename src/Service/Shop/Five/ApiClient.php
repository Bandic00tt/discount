<?php
namespace App\Service\Shop\Five;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class ApiClient
{
    const SITE_URL = 'https://5ka.ru';
    const DOMAIN = '.5ka.ru';
    const GET_DISCOUNTS_URL = '/api/v2/special_offers/';
    const RECORDS_PER_PAGE = 18; // Максимальное кол-во скидок, которое можно получить за 1 запрос
    const LOCATION_ID = 8262; // Пока парсим только Новчик, масштабироваться будем позднее

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30
        ]);
    }

    /**
     * @param int $page
     * @return string
     * @throws GuzzleException
     */
    public function getDiscounts(int $page = 1): string
    {
        try {
            $cookies = ['location_id' => self::LOCATION_ID];
            $cookieJar = CookieJar::fromArray($cookies, self::DOMAIN);

            $response = $this->client->get(self::SITE_URL . self::GET_DISCOUNTS_URL, [
                RequestOptions::QUERY => [
                    'records_per_page' => self::RECORDS_PER_PAGE,
                    'page' => $page,
                ],
                RequestOptions::COOKIES => $cookieJar
            ]);
        } catch (Exception $ex) {
            throw $ex;
        }

        return $response->getBody()->getContents();
    }
}