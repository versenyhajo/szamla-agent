<?php

namespace SzamlaAgent;

use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\DeliveryNote;
use SzamlaAgent\Document\Proforma;
use SzamlaAgent\Document\Receipt\Receipt;
use SzamlaAgent\Document\Receipt\ReverseReceipt;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Document\Invoice\ReverseInvoice;
use SzamlaAgent\Document\Invoice\CorrectiveInvoice;
use SzamlaAgent\Document\Invoice\FinalInvoice;
use SzamlaAgent\Document\Invoice\PrePaymentInvoice;
use SzamlaAgent\Response\SzamlaAgentResponse;


/**
 * A Számla Agent inicializálását, az adatok küldését és fogadását kezelő osztály
 *
 * @package SzamlaAgent
 */
class SzamlaAgent {

    /**
     * Számla Agent API aktuális verzió
     */
    const API_VERSION = '2.8.2';

    /**
     * Számla Agent API url
     */
    const API_URL = 'https://www.szamlazz.hu/szamla/';

    /**
     * Számla Agent API használatához szükséges minimum PHP verzió
     */
    const PHP_VERSION = '5.6';

    /**
     * Alapértelmezett karakterkódolás
     */
    const CHARSET = 'utf-8';

    /**
     * Alapértelmezett süti fájlnév
     */
    const COOKIE_FILENAME = 'cookie.txt';

    /**
     * Alapértelmezett tanúsítvány fájlnév
     */
    const CERTIFICATION_FILENAME = 'cacert.pem';

    /**
     * Tanúsítványok útvonala
     */
    const CERTIFICATION_PATH = __DIR__.DIRECTORY_SEPARATOR.'cert'.DIRECTORY_SEPARATOR;

    /**
     * PDF dokumentumok útvonala
     */
    const PDF_FILE_SAVE_PATH = __DIR__.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR;

    /**
     * XML fájlok útvonala
     */
    const XML_FILE_SAVE_PATH = __DIR__.DIRECTORY_SEPARATOR.'xmls';

    /**
     * Fájl mellékletek útvonala
     */
    const ATTACHMENTS_SAVE_PATH = __DIR__.DIRECTORY_SEPARATOR.'attachments'.DIRECTORY_SEPARATOR;

    /**
     * Naplózási szint
     *
     * 0: LOG_LEVEL_OFF   - nincs naplózás
     * 1: LOG_LEVEL_ERROR - hibák naplózása
     * 2: LOG_LEVEL_WARN  - hibák és figyelmeztetések naplózása
     * 3: LOG_LEVEL_DEBUG - minden típus naplózása (fejlesztői mód)
     *
     * @var int
     */
    private $logLevel;

    /**
     * Naplózási e-mail cím
     * Erre az e-mail címre küldünk üzenetet, ha hiba esemény történik
     *
     * @var string
     */
    private $logEmail = '';

    /**
     * Számla Agent kérés módja
     *
     * 1: CALL_METHOD_LEGACY - natív
     * 2: CALL_METHOD_CURL   - CURL
     * 3: CALL_METHOD_AUTO   - automatikus
     *
     * @var int
     */
    private $callMethod = SzamlaAgentRequest::CALL_METHOD_AUTO;

    /**
     * Tanúsítvány fájlnév
     *
     * @var string
     */
    private $certificationFileName = self::CERTIFICATION_FILENAME;

    /**
     * Cookie fájlnév
     *
     * @var string
     */
    private $cookieFileName = self::COOKIE_FILENAME;

    /**
     * Számla Agent beállítások
     *
     * @var SzamlaAgentSetting
     */
    private $setting;

    /**
     * Az aktuális Agent kérés
     *
     * @var SzamlaAgentRequest
     */
    private $request;

    /**
     * Az aktuális Agent válasz
     *
     * @var SzamlaAgentResponse
     */
    private $response;

    /**
     * @var SzamlaAgent[]
     */
    protected static $agents = [];

    /**
     * Számla Agent létrehozása
     *
     * @param string $username     e-mail cím vagy bejelentkezési név
     * @param string $password     jelszó
     * @param string $apiKey       Számla Agent kulcs
     * @param bool   $downloadPdf  szeretnénk-e letölteni a bizonylatot PDF formátumban
     * @param int    $logLevel     naplózási szint
     * @param int    $responseType válasz típusa (szöveges vagy XML)
     * @param string $aggregator   webáruházat futtató motor neve
     *
     * @throws SzamlaAgentException
     */
    protected function __construct($username, $password, $apiKey, $downloadPdf, $logLevel = Log::LOG_LEVEL_DEBUG,  $responseType = SzamlaAgentResponse::RESULT_AS_TEXT, $aggregator = '') {
        $this->setSetting(new SzamlaAgentSetting($username, $password, $apiKey, $downloadPdf, SzamlaAgentSetting::DOWNLOAD_COPIES_COUNT, $responseType, $aggregator));
        $this->setLogLevel($logLevel);
        $this->writeLog("Számla Agent inicializálása kész (" . (!empty($username) ? 'username: ' . $username : 'apiKey: ' . $apiKey) . ").", Log::LOG_LEVEL_DEBUG);
    }

    /**
     * Számla Agent létrehozása (felhasználónév és jelszóval)
     *
     * @param string $username    e-mail cím vagy bejelentkezési név
     * @param string $password    jelszó
     * @param bool   $downloadPdf szeretnénk-e letölteni a bizonylatot PDF formátumban
     * @param int    $logLevel    naplózási szint
     *
     * @return SzamlaAgent
     * @throws SzamlaAgentException
     *
     * @deprecated 2.5 Nem ajánlott a használata, helyette SzamlaAgentAPI::create($apiKey);
     */
    public static function create($username, $password, $downloadPdf = true, $logLevel = Log::LOG_LEVEL_DEBUG) {
        $index = self::getHash($username);

        $agent = null;
        if (isset(self::$agents[$index])) {
            $agent = self::$agents[$index];
        }

        if ($agent === null) {
            return self::$agents[$index] = new self($username, $password, null, $downloadPdf, $logLevel);
        } else {
            return $agent;
        }
    }

    /**
     * @throws SzamlaAgentException
     */
    function __destruct() {
        $this->writeLog("Számla Agent műveletek befejezve." . PHP_EOL . str_repeat("_",80) . PHP_EOL, Log::LOG_LEVEL_DEBUG);
    }

    /**
     * Létrehozott Számla Agent példány visszaadása
     *
     * @param  string $instanceId  e-mail cím, bejelentkezési név vagy kulcs
     *
     * @return SzamlaAgent
     * @throws SzamlaAgentException
     */
    public static function get($instanceId) {
        $index = self::getHash($instanceId);
        $agent = self::$agents[$index];

        if ($agent === null) {
            if (strpos($instanceId, '@') === false && strlen($instanceId) == SzamlaAgentSetting::API_KEY_LENGTH) {
                throw new SzamlaAgentException(SzamlaAgentException::NO_AGENT_INSTANCE_WITH_APIKEY);
            } else {
                throw new SzamlaAgentException(SzamlaAgentException::NO_AGENT_INSTANCE_WITH_USERNAME);
            }
        }
        return $agent;
    }

    /**
     * @param $username
     *
     * @return string
     */
    protected static function getHash($username) {
        return hash('sha1', $username);
    }

    /**
     * Számla Agent kérés elküldése és a válasz visszaadása
     *
     * @param SzamlaAgentRequest $request
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     * @throws \Exception
     */
    private function sendRequest(SzamlaAgentRequest $request) {
        try {
            $this->setRequest($request);
            $response = new SzamlaAgentResponse($this, $request->send());

            return $response->handleResponse();
        } catch (SzamlaAgentException $sze) {
            throw $sze;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Bizonylat elkészítése
     *
     * @param string   $type
     * @param Document $document
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateDocument($type, Document $document) {
        $request = new SzamlaAgentRequest($this, $type, $document);

        return $this->sendRequest($request);
    }

    /**
     * Számla elkészítése
     *
     * @param Invoice $invoice
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateInvoice(Invoice $invoice) {
        return $this->generateDocument('generateInvoice', $invoice);
    }

    /**
     * Előlegszámla elkészítése
     *
     * @param PrePaymentInvoice $invoice
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generatePrePaymentInvoice(PrePaymentInvoice $invoice) {
        return $this->generateInvoice($invoice);
    }

    /**
     * Végszámla elkészítése
     *
     * @param FinalInvoice $invoice
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateFinalInvoice(FinalInvoice $invoice) {
        return $this->generateInvoice($invoice);
    }

    /**
     * Helyesbítő számla elkészítése
     *
     * @param CorrectiveInvoice $invoice
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateCorrectiveInvoice(CorrectiveInvoice $invoice) {
        return $this->generateInvoice($invoice);
    }

    /**
     * Nyugta elkészítése
     *
     * @param Receipt $receipt
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateReceipt(Receipt $receipt) {
        return $this->generateDocument('generateReceipt', $receipt);
    }

    /**
     * Számla jóváírás rögzítése
     *
     * @param Invoice $invoice
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function payInvoice(Invoice $invoice) {
        return $this->generateDocument('payInvoice', $invoice);
    }

    /**
     * Nyugta elküldése
     *
     * @param Receipt $receipt
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function sendReceipt(Receipt $receipt) {
        return $this->generateDocument('sendReceipt', $receipt);
    }

    /**
     * Számla adatok lekérdezése számlaszám vagy rendelésszám alapján
     *
     * @param string $data
     * @param int    $type
     * @param bool   $downloadPdf
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function getInvoiceData($data, $type = Invoice::FROM_INVOICE_NUMBER, $downloadPdf = false) {
        $invoice = new Invoice();

        if ($type == Invoice::FROM_INVOICE_NUMBER) {
            $invoice->getHeader()->setInvoiceNumber($data);
        } else {
            $invoice->getHeader()->setOrderNumber($data);
        }

        if ($this->getResponseType() !== SzamlaAgentResponse::RESULT_AS_XML) {
            $msg = 'Helytelen beállítási kísérlet a számla adatok lekérdezésénél: Számla adatok letöltéséhez a kérésre adott válasznak xml formátumúnak kell lennie!';
            $this->writeLog($msg, Log::LOG_LEVEL_WARN);
        }

        $this->setDownloadPdf($downloadPdf);
        $this->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);

        return $this->generateDocument('requestInvoiceData', $invoice);
    }

    /**
     * Számla PDF lekérdezés számlaszám vagy rendelésszám alapján
     *
     * @param string $data
     * @param int    $type
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     * @throws \Exception
     */
    public function getInvoicePdf($data, $type = Invoice::FROM_INVOICE_NUMBER) {
        $invoice = new Invoice();

        if ($type == Invoice::FROM_INVOICE_NUMBER) {
            $invoice->getHeader()->setInvoiceNumber($data);
        } else {
            $invoice->getHeader()->setOrderNumber($data);
        }

        if (!$this->isDownloadPdf()) {
            $msg = 'Helytelen beállítási kísérlet a számla PDF lekérdezésénél: Számla letöltéshez a "downloadPdf" paraméternek "true"-nak kell lennie!';
            $this->writeLog($msg, Log::LOG_LEVEL_WARN);
        }
        $this->setDownloadPdf(true);
        return $this->generateDocument('requestInvoicePDF', $invoice);
    }

    /**
     * Nyugta adatok lekérdezése nyugtaszám alapján
     *
     * @param string $receiptNumber nyugtaszám
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     * @throws \Exception
     */
    public function getReceiptData($receiptNumber) {
        return $this->generateDocument('requestReceiptData', new Receipt($receiptNumber));
    }

    /**
     * Nyugta PDF lekérdezése nyugtaszám alapján
     *
     * @param string $receiptNumber nyugtaszám
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     * @throws \Exception
     */
    public function getReceiptPdf($receiptNumber) {
        return $this->generateDocument('requestReceiptPDF', new Receipt($receiptNumber));
    }

    /**
     * Adózó adatainak lekérdezése törzsszám alapján
     * A választ a NAV Online Számla XML formátumában kapjuk vissza
     *
     * @param string $taxPayerId
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function getTaxPayer($taxPayerId) {
        $request  = new SzamlaAgentRequest($this, 'getTaxPayer', new TaxPayer($taxPayerId));
        $this->setResponseType(SzamlaAgentResponse::RESULT_AS_TAXPAYER_XML);
        return $this->sendRequest($request);
    }

    /**
     * Sztornó számla elkészítése
     *
     * @param ReverseInvoice $invoice
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateReverseInvoice(ReverseInvoice $invoice) {
        return $this->generateDocument('generateReverseInvoice', $invoice);
    }

    /**
     * Sztornó nyugta elkészítése
     *
     * @param ReverseReceipt $receipt
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateReverseReceipt(ReverseReceipt $receipt) {
        return $this->generateDocument('generateReverseReceipt', $receipt);
    }

    /**
     * Díjbekérő elkészítése
     *
     * @param Proforma $proforma
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateProforma(Proforma $proforma) {
        return $this->generateDocument('generateProforma', $proforma);
    }

    /**
     * Díjbekérő törlése számlaszám vagy rendelésszám alapján
     *
     * @param string $data
     * @param int    $type
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     * @throws \Exception
     */
    public function getDeleteProforma($data, $type = Proforma::FROM_INVOICE_NUMBER) {
        $proforma = new Proforma();

        if ($type == Proforma::FROM_INVOICE_NUMBER) {
            $proforma->getHeader()->setInvoiceNumber($data);
        } else {
            $proforma->getHeader()->setOrderNumber($data);
        }

        $this->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);
        $this->setDownloadPdf(false);

        return $this->generateDocument('deleteProforma', $proforma);
    }

    /**
     * Szállítólevél elkészítése
     *
     * @param DeliveryNote $deliveryNote
     *
     * @return SzamlaAgentResponse
     * @throws SzamlaAgentException
     */
    public function generateDeliveryNote(DeliveryNote $deliveryNote) {
        return $this->generateDocument('generateDeliveryNote', $deliveryNote);
    }

    /**
     * @param string $message
     * @param int    $type
     *
     * @return bool
     * @throws SzamlaAgentException
     */
    public function writeLog($message, $type = Log::LOG_LEVEL_DEBUG) {
        if ($this->logLevel < $type) {
            return false;
        }

        if ($this->logLevel != Log::LOG_LEVEL_OFF) {
            Log::writeLog($message, $type, $this->logEmail);
        }
        return true;
    }

    /**
     * @param $message
     *
     * @throws SzamlaAgentException
     */
    public function logError($message) {
        $this->writeLog($message, Log::LOG_LEVEL_ERROR);
    }

    /**
     * @return string
     */
    public function getApiVersion() {
        return self::API_VERSION;
    }

    /**
     * Visszaadja a naplózási szintet
     *
     * @return int
     */
    public function getLogLevel() {
        return $this->logLevel;
    }

    /**
     * Beállítja a naplózási szintet
     *
     * 0: LOG_LEVEL_OFF   - nincs naplózás
     * 1: LOG_LEVEL_ERROR - hibák naplózása
     * 2: LOG_LEVEL_WARN  - hibák és figyelmeztetések naplózása
     * 3: LOG_LEVEL_DEBUG - minden típus naplózása (fejlesztői mód)
     *
     * @var int
     */
    public function setLogLevel($logLevel) {
        if (Log::isNotValidLogLevel($logLevel)) {
            $logLevel = Log::LOG_LEVEL_DEBUG;
        }
        $this->logLevel = $logLevel;
    }

    /**
     * Visszaadja a Számla Agent kérés módját
     *
     * @return int
     */
    public function getCallMethod() {
        return $this->callMethod;
    }

    /**
     * Beállítja a Számla Agent kérés módját
     *
     * 1: CALL_METHOD_LEGACY - natív
     * 2: CALL_METHOD_CURL   - CURL
     * 3: CALL_METHOD_AUTO   - automatikus
     *
     * @param int $callMethod
     */
    public function setCallMethod($callMethod) {
        $this->callMethod = $callMethod;
    }

    /**
     * @return string
     */
    public function getLogEmail() {
        return $this->logEmail;
    }

    /**
     * @param string $logEmail
     */
    public function setLogEmail($logEmail) {
        $this->logEmail = $logEmail;
    }

    /**
     * @return string
     */
    public function getCertificationFile() {
        return SzamlaAgentUtil::getAbsPath(self::CERTIFICATION_PATH, self::CERTIFICATION_FILENAME);
    }

    /**
     * @return string
     */
    public function getCertificationFileName() {
        return $this->certificationFileName;
    }

    /**
     * @param string $certificationFileName
     */
    public function setCertificationFileName($certificationFileName) {
        $this->certificationFileName = $certificationFileName;
    }

    /**
     * @return string
     */
    public function getCookieFileName() {
        return $this->cookieFileName;
    }

    /**
     * @param string $cookieFile
     */
    public function setCookieFileName($cookieFile) {
        $this->cookieFileName = $cookieFile;
    }

    /**
     * @return SzamlaAgentSetting
     */
    public function getSetting() {
        return $this->setting;
    }

    /**
     * @param SzamlaAgentSetting $setting
     */
    public function setSetting($setting) {
        $this->setting = $setting;
    }

    /**
     * Visszaadja a már létrehozott Számla Agent példányokat
     *
     * @return SzamlaAgent[]
     */
    public static function getAgents() {
        return self::$agents;
    }

    /**
     * Visszaadja a Számla Agent kéréshez használt felhasználónevet
     *
     * @return string
     */
    public function getUsername() {
        return $this->getSetting()->getUsername();
    }

    /**
     * Beállítja a Számla Agent kéréshez használt felhasználónevet
     * A felhasználónév a https://www.szamlazz.hu/szamla/login oldalon használt e-mail cím vagy bejelentkezési név.
     *
     * @param $username
     */
    public function setUsername($username) {
        $this->getSetting()->setUsername($username);
    }

    /**
     * Visszaadja a Számla Agent kéréshez használt jelszót
     *
     * @return string
     */
    public function getPassword() {
        return $this->getSetting()->getPassword();
    }

    /**
     * Beállítja a Számla Agent kéréshez használt jelszót
     * A jelszó a https://www.szamlazz.hu/szamla/login/ oldalon használt bejelentkezési jelszó.
     *
     * @param $password
     */
    public function setPassword($password) {
        $this->getSetting()->setPassword($password);
    }

    /**
     * Visszaadja a Számla Agent kéréshez használt kulcsot
     *
     * @return string
     */
    public function getApiKey() {
        return $this->getSetting()->getApiKey();
    }

    /**
     * Beállítja a Számla Agent kéréshez használt kulcsot
     *
     * @link  https://www.szamlazz.hu/blog/2019/07/szamla_agent_kulcsok/
     * @param string $apiKey
     */
    public function setApiKey($apiKey) {
        $this->getSetting()->setApiKey($apiKey);
    }

    /**
     * Visszaadja a kulcstartót nyitó jelszó
     *
     * @return string
     */
    public function getKeychain() {
        return $this->getSetting()->getKeychain();
    }

    /**
     * Beállítja a kulcstartót nyitó jelszó (e-számla esetén)
     *
     * @param $keychain
     */
    public function setKeychain($keychain) {
        $this->getSetting()->setKeychain($keychain);
    }

    /**
     * Visszaadja, hogy a Agent válaszában megkapjuk-e a számlát PDF-ként
     *
     * @return bool
     */
    public function isDownloadPdf() {
        return $this->getSetting()->isDownloadPdf();
    }

    /**
     * Beállítja, hogy a Agent válaszában megkapjuk-e a számlát PDF-ként
     *
     * @param bool $downloadPdf
     */
    public function setDownloadPdf($downloadPdf) {
        $this->getSetting()->setDownloadPdf($downloadPdf);
    }

    /**
     * Visszaadja a letöltendő PDF-ben szereplő bizonylat másolatainak számát
     *
     * @return int
     */
    public function getDownloadCopiesCount() {
        return $this->getSetting()->getDownloadCopiesCount();
    }

    /**
     * Letöltendő bizonylat másolat számának beállítása
     *
     * Amennyiben az Agenttel papír alapú számlát készítesz és kéred a számlaletöltést ($downloadPdf = true),
     * akkor opcionálisan megadható, hogy nem csak a számla eredeti példányát kéred, hanem a másolatot is egyetlen pdf-ben.
     *
     * @param int $downloadCopiesCount
     */
    public function setDownloadCopiesCount($downloadCopiesCount) {
        $this->getSetting()->setDownloadCopiesCount($downloadCopiesCount);
    }

    /**
     * Visszaadja a Számla Agent válaszának típusát
     *
     * @return int
     */
    public function getResponseType() {
        return $this->getSetting()->getResponseType();
    }

    /**
     * Számla Agent válasz típusának beállítása
     *
     * 1: RESULT_AS_TEXT - egyszerű szöveges válaszüzenetet vagy pdf-et ad vissza.
     * 2: RESULT_AS_XML  - xml válasz, ha kérted a pdf-et az base64 kódolással benne van az xml-ben.
     *
     * @param int $responseType
     */
    public function setResponseType($responseType) {
        $this->getSetting()->setResponseType($responseType);
    }

    /**
     * Visszaadja a bérelhető webáruházat futtató motor nevét
     *
     * @return string
     */
    public function getAggregator() {
        return $this->getSetting()->getAggregator();
    }

    /**
     * Ha bérelhető webáruházat üzemeltetsz, beállítja a webáruházat futtató motor nevét.
     * Ha nem vagy benne biztos, akkor kérd ügyfélszolgálatunk segítségét (info@szamlazz.hu).
     * (pl. WooCommerce, OpenCart, PrestaShop, Shoprenter, Superwebáruház, Drupal invoice Agent, stb.)
     *
     * @param string $aggregator
     */
    public function setAggregator($aggregator) {
        $this->getSetting()->setAggregator($aggregator);
    }

    /**
     * @return SzamlaAgentRequest
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @param SzamlaAgentRequest $request
     */
    public function setRequest($request) {
        $this->request = $request;
    }

    /**
     * @return SzamlaAgentResponse
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @param SzamlaAgentResponse $response
     */
    public function setResponse($response) {
        $this->response = $response;
    }

    /**
     * @return Log
     */
    public function getLog() {
        return Log::get();
    }

}
