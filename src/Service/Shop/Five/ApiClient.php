<?php
namespace App\Service\Shop\Five;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class ApiClient
{
    const SITE_URL = 'https://5ka.ru';
    const DOMAIN = '.5ka.ru';
    const GET_DISCOUNTS_URL = '/api/v2/special_offers/';
    const GET_REGIONS_URL = '/api/regions/';
    const RECORDS_PER_PAGE = 18; // Максимальное кол-во скидок, которое можно получить за 1 запрос

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30
        ]);
    }

    /**
     * @param int $locationId
     * @param int $page
     * @return string
     * @throws GuzzleException
     */
    public function getDiscounts(int $locationId, int $page): string
    {
        try {
            $cookies = ['location_id' => $locationId];
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

    /**
     * @return string
     * @throws GuzzleException
     */
    public function getRegions(): string
    {
        try {
            $response = $this->client->get(self::SITE_URL . self::GET_REGIONS_URL);
        } catch (Exception $ex) {
            throw $ex;
        }

        return $response->getBody()->getContents();
    }

    /**
     * @param int $regionId
     * @return string
     * @throws GuzzleException
     */
    public function getRegionCities(int $regionId): string
    {
        try {
            $response = $this->client->get(self::SITE_URL . self::GET_REGIONS_URL . $regionId .'/');
        } catch (Exception $ex) {
            throw $ex;
        }

        return $response->getBody()->getContents();
    }
}