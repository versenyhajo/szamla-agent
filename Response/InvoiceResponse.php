<?php

namespace SzamlaAgent\Response;

/**
 * Egy számla típusú bizonylat kérésére adott választ reprezentáló osztály
 *
 * @package SzamlaAgent\Response
 */
class InvoiceResponse {

    /**
     * Vevői fiók URL
     *
     * @var string
     */
    protected $userAccountUrl;

    /**
     * Kintlévőség
     *
     * @var int
     */
    protected $assetAmount;

    /**
     * Nettó végösszeg
     *
     * @var int
     */
    protected $netPrice;

    /**
     * Bruttó végösszeg
     *
     * @var int
     */
    protected $grossAmount;

    /**
     * Számlaszám
     *
     * @var string
     */
    protected $invoiceNumber;

    /**
     * A válasz hibakódja
     *
     * @var string
     */
    protected $errorCode;

    /**
     * A válasz hibaüzenete
     *
     * @var string
     */
    protected $errorMessage;

    /**
     * A válaszban kapott PDF adatai
     *
     * @var string
     */
    protected $pdfData;

    /**
     * Sikeres-e a válasz
     *
     * @var bool
     */
    protected $success;

    /**
     * A válasz fejléc adatai
     *
     * @var array
     */
    protected $headers;

    /**
     * Számla válasz létrehozása
     *
     * @param string $invoiceNumber
     */
    public function __construct($invoiceNumber = '') {
        $this->setInvoiceNumber($invoiceNumber);
    }

    /**
     * Feldolgozás után visszaadja a számla válaszát objektumként
     *
     * @param array $data
     * @param int   $type
     *
     * @return InvoiceResponse
     */
    public static function parseData(array $data, $type = SzamlaAgentResponse::RESULT_AS_TEXT) {
        $payer   = new InvoiceResponse();
        $headers = $data['headers'];
        $isPdf   = self::isPdfResponse($data);
        $pdfFile = '';

        if (isset($data['body'])) {
            $pdfFile = $data['body'];
        } else if ($type == SzamlaAgentResponse::RESULT_AS_XML && isset($data['pdf'])) {
            $pdfFile = $data['pdf'];
        }

        if (!empty($headers)) {
            $payer->setHeaders($headers);

            if (array_key_exists('szlahu_szamlaszam', $headers)) {
                $payer->setInvoiceNumber($headers['szlahu_szamlaszam']);
            }

            if (array_key_exists('szlahu_vevoifiokurl', $headers)) {
                $payer->setUserAccountUrl(rawurldecode($headers['szlahu_vevoifiokurl']));
            }

            if (array_key_exists('szlahu_kintlevoseg', $headers)) {
                $payer->setAssetAmount($headers['szlahu_kintlevoseg']);
            }

            if (array_key_exists('szlahu_nettovegosszeg', $headers)) {
                $payer->setNetPrice($headers['szlahu_nettovegosszeg']);
            }

            if (array_key_exists('szlahu_bruttovegosszeg', $headers)) {
                $payer->setGrossAmount($headers['szlahu_bruttovegosszeg']);
            }

            if (array_key_exists('szlahu_error', $headers)) {
                $error = urldecode($headers['szlahu_error']);
                $payer->setErrorMessage($error);
            }

            if (array_key_exists('szlahu_error_code', $headers)) {
                $payer->setErrorCode($headers['szlahu_error_code']);
            }

            if ($isPdf && !empty($pdfFile)) {
                $payer->setPdfData($pdfFile);
            }

            if ($payer->isNotError()) {
                $payer->setSuccess(true);
            }
        }
        return $payer;
    }

    /**
     * Visszaadja, hogy a válasz tartalmaz-e PDF-et
     *
     * @param $result
     *
     * @return bool
     */
    protected static function isPdfResponse($result) {
        if (isset($result['pdf'])) {
            return true;
        }

        if (isset($result['headers']['Content-Type']) && $result['headers']['Content-Type'] == 'application/pdf') {
            return true;
        }

        if (isset($result['headers']['Content-Disposition']) && stripos($result['headers']['Content-Disposition'],'pdf') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Visszaadja a számlaszámot
     *
     * @return string
     */
    public function getInvoiceNumber() {
        return $this->invoiceNumber;
    }

    /**
     * Visszaadja a bizonylat (számla) számát
     *
     * @return string
     */
    public function getDocumentNumber() {
        return $this->getInvoiceNumber();
    }

    /**
     * @param string $invoiceNumber
     */
    protected function setInvoiceNumber($invoiceNumber) {
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * Visszaadja a válasz hibakódját
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
     * Visszaadja a válasz hibaüzenetét
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
     * @return bool|string
     */
    public function getPdfFile() {
        return base64_decode($this->getPdfData());
    }

    /**
     * Visszaadja a számlához tartozó PDF adatait
     *
     * @return string
     */
    public function getPdfData() {
        return $this->pdfData;
    }

    /**
     * @param string $pdfData
     */
    protected function setPdfData($pdfData) {
        $this->pdfData = $pdfData;
    }

    /**
     * Visszaadja a válasz sikerességét
     *
     * @return bool
     */
    public function isSuccess() {
        return ($this->success && $this->isNotError());
    }

    /**
     * Visszaadja, hogy a válasz tartalmaz-e hibát
     *
     * @return bool
     */
    public function isError() {
        return (!empty($this->getErrorMessage()) || !empty($this->getErrorCode()));
    }

    /**
     * Visszaadja, hogy nem történt-e hiba
     *
     * @return bool
     */
    public function isNotError() {
        return !$this->isError();
    }

    /**
     * @param bool $success
     */
    protected function setSuccess($success) {
        $this->success = $success;
    }

    /**
     * Visszaadja a vevői fiók URL-jét
     *
     * @return string
     */
    public function getUserAccountUrl() {
        return urldecode($this->userAccountUrl);
    }

    /**
     * @param string $userAccountUrl
     */
    protected function setUserAccountUrl($userAccountUrl) {
        $this->userAccountUrl = $userAccountUrl;
    }

    /**
     * Visszaadja a kintlévőség összegét
     *
     * @return int
     */
    public function getAssetAmount() {
        return $this->assetAmount;
    }

    /**
     * @param int $assetAmount
     */
    protected function setAssetAmount($assetAmount) {
        $this->assetAmount = $assetAmount;
    }

    /**
     * Visszaadja a nettó összeget
     *
     * @return int
     */
    public function getNetPrice() {
        return $this->netPrice;
    }

    /**
     * @param int $netPrice
     */
    protected function setNetPrice($netPrice) {
        $this->netPrice = $netPrice;
    }

    /**
     * Visszaadja a bruttó összeget
     *
     * @return int
     */
    public function getGrossAmount() {
        return $this->grossAmount;
    }

    /**
     * @param $grossAmount
     */
    protected function setGrossAmount($grossAmount) {
        $this->grossAmount = $grossAmount;
    }

    /**
     * Visszaadja a válasz fejléc adatait
     *
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    protected function setHeaders($headers) {
        $this->headers = $headers;
    }
}