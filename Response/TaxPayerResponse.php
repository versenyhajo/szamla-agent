<?php

namespace SzamlaAgent\Response;

/**
 * Egy adózó adatainak lekérésére adott választ reprezentáló osztály
 *
 * @package SzamlaAgent\Response
 */
class TaxPayerResponse {

    /**
     * Kérés azonosító
     *
     * @var string
     */
    protected $requestId;

    /**
     * Kérés időbélyege
     *
     * @var string
     */
    protected $timestamp;

    /**
     * Kérés verziója
     *
     * @var string
     */
    protected $requestVersion;

    /**
     * Kérés sikeres volt-e
     *
     * @var string
     */
    protected $funcCode;

    /**
     * Adószám érvényes-e?
     *
     * @var bool
     */
    protected $taxpayerValidity;

    /**
     * Adózó neve
     *
     * @var string
     */
    protected $taxpayerName;

    /**
     * Adózói címének országa
     *
     * @var string
     */
    protected $countryCode;

    /**
     * Adózó címének irányítószáma
     *
     * @var string
     */
    protected $postalCode;

    /**
     * Adózói címének városa
     *
     * @var string
     */
    protected $city;

    /**
     * Adózói címének közterület elnevezése
     *
     * @var string
     */
    protected $streetName;

    /**
     * Adózó címének közterület típusa
     *
     * @var string
     */
    protected $publicPlaceCategory;

    /**
     * Adózó címének házszáma
     *
     * @var string
     */
    protected $number;

    /**
     * A válaszban visszakapott adózói adatok
     *
     * @var array
     */
    private $taxPayerData;

    /**
     * Hibakód
     *
     * @var string
     */
    protected $errorCode;

    /**
     * Hibaüzenet
     *
     * @var string
     */
    protected $errorMessage;


    /**
     * Adózó lekérdezésének adatai
     *
     * @param string $taxpayerName
     * @param string $countryCode
     * @param string $postalCode
     * @param string $city
     */
    function __construct($taxpayerName = '', $countryCode = '', $postalCode = '', $city = '') {
        $this->setTaxpayerName($taxpayerName);
        $this->setCountryCode($countryCode);
        $this->setPostalCode($postalCode);
        $this->setCity($city);
    }

    /**
     * Feldolgozás után visszaadja az adózó válaszát objektumként
     *
     * @param array $data
     *
     * @return TaxPayerResponse
     */
    public static function parseData(array $data) {
        $payer = new TaxPayerResponse();

        if (isset($data['result']['funcCode']))  $payer->setFuncCode($data['result']['funcCode']);
        if (isset($data['result']['errorCode'])) $payer->setErrorCode($data['result']['errorCode']);
        if (isset($data['result']['message']))   $payer->setErrorMessage($data['result']['message']);
        if (isset($data['taxpayerValidity']))    $payer->setTaxpayerValidity(($data['taxpayerValidity'] === 'true'));

        if (isset($data['header'])) {
            $header = $data['header'];
            $payer->setRequestId($header['requestId']);
            $payer->setTimestamp($header['timestamp']);
            $payer->setRequestVersion($header['requestVersion']);
        }

        if (isset($data['taxpayerData'])) {
            $payerData = $data['taxpayerData'];
            $address = $payerData['taxpayerAddress'];

            $payer->setTaxpayerName($payerData['taxpayerName']);
            $payer->setCountryCode($address['countryCode']);
            $payer->setPostalCode($address['postalCode']);
            $payer->setCity($address['city']);
            $payer->setStreetName($address['streetName']);
            $payer->setPublicPlaceCategory($address['publicPlaceCategory']);
            $payer->setNumber($address['number']);
            $payer->setTaxPayerData($payerData);
        }
        return $payer;
    }

    /**
     * Visszaadja a válasz azonosítóját
     *
     * @return string
     */
    public function getRequestId() {
        return $this->requestId;
    }

    /**
     * @param string $requestId
     */
    protected function setRequestId($requestId) {
        $this->requestId = $requestId;
    }

    /**
     * Visszaadja a válasz időbélyegét
     *
     * @return string
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     */
    protected function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * Visszaadja a kérés verzióját
     *
     * @return string
     */
    public function getRequestVersion() {
        return $this->requestVersion;
    }

    /**
     * @param string $requestVersion
     */
    protected function setRequestVersion($requestVersion) {
        $this->requestVersion = $requestVersion;
    }

    /**
     * Visszaadja a kérés sikerességét
     *
     * @return string
     */
    public function getFuncCode() {
        return $this->funcCode;
    }

    /**
     * @param string $funcCode
     */
    protected function setFuncCode($funcCode) {
        $this->funcCode = $funcCode;
    }

    /**
     * Visszaadja, hogy az adószám érvényes-e
     *
     * @return string
     */
    public function isTaxpayerValidity() {
        return $this->taxpayerValidity;
    }

    /**
     * @param string $taxpayerValidity
     */
    protected function setTaxpayerValidity($taxpayerValidity) {
        $this->taxpayerValidity = $taxpayerValidity;
    }

    /**
     * Visszaadja az adózó nevét
     *
     * @return string
     */
    public function getTaxpayerName() {
        return $this->taxpayerName;
    }

    /**
     * @param string $taxpayerName
     */
    protected function setTaxpayerName($taxpayerName) {
        $this->taxpayerName = $taxpayerName;
    }

    /**
     * Visszaadja az adózó címének országát
     *
     * @return string
     */
    public function getCountryCode() {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     */
    protected function setCountryCode($countryCode) {
        $this->countryCode = $countryCode;
    }

    /**
     * Visszaadja az adózó címének irányítószámát
     * @return string
     */
    public function getPostalCode() {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     */
    protected function setPostalCode($postalCode) {
        $this->postalCode = $postalCode;
    }

    /**
     * Visszaadja az adózó címének városát
     *
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * @param string $city
     */
    protected function setCity($city) {
        $this->city = $city;
    }

    /**
     * Visszaadja, hogy a válaszban vannak-e adózói adatok
     *
     * @return bool
     */
    public function hasTaxPayerData() {
        return (!empty($this->taxPayerData));
    }

    /**
     * Visszaadja az adózó címének közterület elnevezését
     *
     * @return string
     */
    public function getStreetName() {
        return $this->streetName;
    }

    /**
     * @param string $streetName
     */
    protected function setStreetName($streetName) {
        $this->streetName = $streetName;
    }

    /**
     * Visszaadja az adózó címének közterület típusát
     *
     * @return string
     */
    public function getPublicPlaceCategory() {
        return $this->publicPlaceCategory;
    }

    /**
     * @param string $publicPlaceCategory
     */
    protected function setPublicPlaceCategory($publicPlaceCategory) {
        $this->publicPlaceCategory = $publicPlaceCategory;
    }

    /**
     * Visszaadja az adózó címének házszámát
     *
     * @return string
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * @param string $number
     */
    protected function setNumber($number) {
        $this->number = $number;
    }

    /**
     * Visszaadja az adózó adatait
     *
     * @return array
     */
    public function getTaxPayerData() {
        return $this->taxPayerData;
    }

    /**
     * @param array
     */
    protected function setTaxPayerData(array $data) {
        $this->taxPayerData = $data;
    }

    /**
     * Visszaadja az adózó lekérdezésének sikerességét
     *
     * @return bool
     */
    public function isSuccess() {
        return ($this->getFuncCode() == 'OK');
    }

    /**
     * Visszaadja, hogy a válasz tartalmaz-e hibát
     *
     * @return bool
     */
    public function isError() {
        return !$this->isSuccess();
    }

    /**
     * Visszaadja a hibakódot
     *
     * @return string
     */
    public function getErrorCode() {
        return $this->errorCode;
    }

    /**
     * @param string $errorCode
     */
    protected function setErrorCode($errorCode) {
        $this->errorCode = $errorCode;
    }

    /**
     * Visszaadja a hibaüzenetet
     *
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    protected function setErrorMessage($errorMessage) {
        $this->errorMessage = $errorMessage;
    }

    /**
     * Visszaadja az adózó adatait
     *
     * @return string
     */
    public function getTaxPayerStr() {
        $validity = ($this->isTaxpayerValidity()) ? 'érvényes' : "érvénytelen";

        $str = '';
        if (!empty($this->getTaxPayerData())) {
            $str = "Adózó adatai:" . PHP_EOL;
            $str.= "Név: {$this->getTaxpayerName()} (" . $validity . ")" . PHP_EOL;
            $str.= "Cím: {$this->getCountryCode()} {$this->getPostalCode()} {$this->getCity()}, {$this->getStreetName()} {$this->getPublicPlaceCategory()} {$this->getNumber()} ";
        } else {
            if ($this->getFuncCode()) {
                $str = "Ehhez az adószámhoz nem található adat!";
            }
        }
        return $str;
    }
 }