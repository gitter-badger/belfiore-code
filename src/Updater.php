<?php

namespace Devnix\BelfioreCode;

use Devnix\BelfioreCode\Converter\CitiesConverter;
use Devnix\BelfioreCode\Converter\RegionsConverter;
use Devnix\ZipException\ZipException;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;
use ZipArchive;

final class Updater
{
    /**
     * @var string
     *
     * @link https://developers.italia.it/en/anpr
     * @license cc-by-3.0
     */
    protected const CITIES = 'https://raw.githubusercontent.com/italia/anpr/master/src/archivi/ANPR_archivio_comuni.csv';

    /**
     * @var string
     * @link https://www.istat.it
     * @license cc-by-4.0
     */
    protected const REGIONS = 'https://www.istat.it/it/files/2011/01/Elenco-codici-e-denominazioni-unita-territoriali-estere-1.zip';

    /**
     * @var string
     */
    protected const DESTINATION = __DIR__.'/../dist';

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var string
     */
    protected $citiesPath;

    /**
     * @var string
     */
    protected $regionsPath;

    public function __construct()
    {
        $this->serializer = new Serializer([], [new CsvEncoder(), new XmlEncoder(), new JsonEncoder()]);

        $this->citiesPath = $this->downloadTmpCities();
        $this->regionsPath = $this->downloadTmpRegions();
    }

    public function __destruct()
    {
        $this->removeTmpCities();
        $this->removeTmpRegions();
    }

    /**
     * Fetch and update all the data
     *
     * @return null
     */
    public function generateCities(): void
    {
        $this->createDistDirectory();

        $citiesConverter = new CitiesConverter($this->citiesPath);

        file_put_contents(self::DESTINATION.'/cities.csv', $citiesConverter->getCsv());
        file_put_contents(self::DESTINATION.'/cities.xml', $citiesConverter->getXml());
        file_put_contents(self::DESTINATION.'/cities.json', $citiesConverter->getJson());
        file_put_contents(self::DESTINATION.'/cities.yaml', $citiesConverter->getYaml());
    }

    public function generateRegions()
    {
        $this->createDistDirectory();

        $regionsConverter = new RegionsConverter($this->regionsPath);

        file_put_contents(self::DESTINATION.'/regions.csv', $regionsConverter->getCsv());
        file_put_contents(self::DESTINATION.'/regions.xml', $regionsConverter->getXml());
        file_put_contents(self::DESTINATION.'/regions.json', $regionsConverter->getJson());
        file_put_contents(self::DESTINATION.'/regions.yaml', $regionsConverter->getYaml());
    }

    protected function createDistDirectory(): void
    {
        if (!is_dir(self::DESTINATION)) {
            ErrorHandler::call('mkdir', self::DESTINATION);
        }
    }

    protected function downloadTmpCities(): string
    {
        return ErrorHandler::call(static function () {
            $tmpPath = tempnam(sys_get_temp_dir(), 'devnix-belfiore-code-cities.csv.');

            file_put_contents($tmpPath, file_get_contents(self::CITIES));

            return $tmpPath;
        });
    }

    protected function downloadTmpRegions(): string
    {
        return ErrorHandler::call(static function () {
            $tmpZip = tempnam(sys_get_temp_dir(), 'devnix-belfiore-code-cities.zip.');
            $tmpPath = tempnam(sys_get_temp_dir(), 'devnix-belfiore-code-regions.xlsx.');

            file_put_contents($tmpZip, file_get_contents(self::REGIONS));

            $zip = new ZipArchive();

            if (!$zipStatus = $zip->open($tmpZip)) {
                throw new ZipException($zipStatus);
            }

            file_put_contents($tmpPath, $zip->getFromName('Elenco-codici-e-denominazioni-unita-territoriali-estere/Elenco-codici-e-denominazioni-al-31_12_2019.xlsx'));

            return $tmpPath;
        });
    }

    protected function removeTmpCities(): void
    {
        if (empty($this->citiesPath)) {
            return;
        }

        try {
            ErrorHandler::call('unlink', $this->citiesPath);
        } catch (\Exception $e) {};

        unset($this->citiesPath);
    }

    protected function removeTmpRegions(): void
    {
        if (empty($this->citiesPath)) {
            return;
        }

        try {
            ErrorHandler::call('unlink', $this->regionsPath);
        } catch (\Exception $e) {};

        unset($this->citiesPath);
    }
}
