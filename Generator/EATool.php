<?php

namespace Wandi\EasyAdminBundle\Generator\Service;

use Wandi\EasyAdminBundle\Exception\EAException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class EATool
{
    private $parameters = [];
    private $entities;
    private static $translation = null;
    private static $parameterBag = null;

    /**
     * EATool constructor.
     */
    public function __construct()
    {
        $this->entities = new ArrayCollection();
    }

    /**
     * @return null
     */
    public static function getTranslation()
    {
        return self::$translation;
    }

    /**
     * @return null
     */
    public static function getParameterBag()
    {
        return self::$parameterBag;
    }

    /**
     * @param null $parameterBag
     */
    public static function setParameterBag($parameterBag)
    {
        self::$parameterBag = $parameterBag;
    }

    /**
     * @return ArrayCollection
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param ArrayCollection $entities
     * @return $this
     */
    public function setEntities(ArrayCollection $entities)
    {
        $this->entities = $entities;
        return $this;
    }

    /**
     * @param Entity $entity
     * @return $this
     */
    public function addEntity(Entity $entity)
    {
        $this->entities[] = $entity;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getMenuStructure() : array
    {
        $entitiesMenuStructure = [];

        foreach($this->getEntities()->getIterator()  as $entity)
        {
            $entitiesMenuStructure[] = self::buildEntryMenu($entity->getName());
        }

        $structure = [
            'easy_admin' => [
                'design' => [
                    'menu' => $entitiesMenuStructure
                ]
            ]
        ];

        return $structure;
    }

    /**
     * @param $fileName
     * @param $projectDir
     */
    public function initTranslation($fileName, $projectDir): void
    {
        if (self::$translation === null)
        {
            self::$translation = new Translator('fr_FR');
            self::$translation->addLoader('yaml', new YamlFileLoader());
            self::$translation->addResource('yaml', $projectDir."/app/Resources/translations/".$fileName.".fr.yml", 'fr_FR');
        }
    }

    public function initHelpers(): void
    {
        $classHelpers = array_map(function($helper){
            return array_replace(ConfigurationTypes::getMaskHelper(), $helper);
        }, ConfigurationTypes::getClassHelpers());

        ConfigurationTypes::setClassHelpers($classHelpers);

        $typeHelpers = array_map(function($helper){
            return array_replace(ConfigurationTypes::getMaskHelper(), $helper);
        }, ConfigurationTypes::getTypeHelpers());

        ConfigurationTypes::setTypeHelpers($typeHelpers);
    }

    /**
     * @param $projectDir
     * @param ConsoleOutput $consoleOutput
     * @throws EAException
     */
    public function generateMenuFile($projectDir, ConsoleOutput $consoleOutput): void
    {
        $ymlContent = self::buildDumpPhpToYml($this->getMenuStructure(), $this->parameters);
        $path =  '/app/config/easyadmin/' . $this->parameters['pattern_file'] . '_menu.yml';
        if (false !== file_put_contents($projectDir . $path, $ymlContent))
            $consoleOutput->writeln('The file <comment>' . $path . ' </comment>has been <info>generated</info>.');
        else
            throw new EAException('Unable to generate the menu file, the generation process is <info>stopped</info>');
    }

    /**
     * @param $projectDir
     * @param ConsoleOutput $consoleOutput
     * @throws EAException
     */
    public function generateEntityFiles($projectDir, ConsoleOutput $consoleOutput): void
    {
        foreach($this->getEntities()->getIterator()  as $entity)
        {
            $ymlContent = self::buildDumpPhpToYml($entity->getStructure($this->parameters), $this->parameters);
            $path = '/app/config/easyadmin/' . $this->parameters['pattern_file'] . '_' . $entity->getName() . '.yml';
            $consoleOutput->writeln('Generating entity "<info>' . $entity->getName() . '</info>"');
            self::createBackupFile($entity->getName(), $projectDir . $path, $consoleOutput);

            if (file_put_contents($projectDir . $path, $ymlContent ))
                $consoleOutput->writeln('  > generating <comment>' . $path . '</comment>');
            else
                throw new EAException('Unable to generate the configuration file for the ' . $entity->getName() . ' entity, the generation process is stopped');
        }
    }

    /**
     * @return array
     */
    private function getBaseFileStructure(): array
    {
        $importFiles = [];

        foreach($this->getEntities()->getIterator()  as $entity)
        {
            $importFiles[] = ['resource' => $this->parameters['pattern_file'] . '_' . $entity->getName() . '.yml'];
        }

        //ajoute fichier menu
        $importFiles[] = ['resource' => $this->parameters['pattern_file'] . '_menu.yml'];

        $structure = [
            'imports' => $importFiles,
            'easy_admin' => [
                'translation_domain' => $this->parameters['translation_domain'],
                'formats' => [
                    'datetime' => 'd/m/Y à H\hi e',
                    'date' => 'd/m/Y',
                    'time' => 'H\hi e'
                ],
                'site_name' => $this->parameters['name_backend'],
                'design' => [
                    'brand_color' => '#D9262D',
                    'assets' => [
                        'js' => $this->parameters['assets']['js'],
                    ]
                ]
            ]
        ];

        return $structure;
    }

    /**
     * @param $projectDir
     * @param ConsoleOutput $consoleOutput
     * @throws EAException
     */
    public function generateBaseFile($projectDir, ConsoleOutput $consoleOutput): void
    {
        $ymlContent = self::buildDumpPhpToYml($this->getBaseFileStructure(), $this->parameters);
        $path = '/app/config/easyadmin/' . $this->parameters['pattern_file'] . '.yml';

        // on fait une backup, créer une méthode

        if (file_put_contents($projectDir . $path, $ymlContent ))
            $consoleOutput->writeln('The <info>main</info> configuration file <comment>' . $path . '</comment> has been <info>generated</info>.');
        else
            throw new EAException('Unable to generate the main configuration file , the generation process is <info>stopped</info>');
    }

    /**
     * Dumps a PHP value to YML.
     * @param array $phpContent
     * @param array $parameters
     * @return string
     */
    public static function buildDumpPhpToYml(array $phpContent, array $parameters): string
    {
        $dumper = new Dumper($parameters['dump_indentation']);
        $yml = $dumper->dump($phpContent, $parameters['dump_inline'], 0, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        return $yml;
    }

    public static function buildEntryMenu($nameEntity)
    {
        return [
            'entity' => $nameEntity,
            'label' => str_replace('_', ' ', preg_replace('/(?<! )(?<!^)[A-Z]/',' $0', $nameEntity))
        ];
    }

    public static function createBackupFile($fileName, $filePath, ConsoleOutput $consoleOutput)
    {
        if (file_exists($filePath))
        {
            if (true === copy($filePath, $filePath . '~'))
                $consoleOutput->writeln('  > Backing up <comment>' . $fileName . '.php</comment> to <comment>' . $fileName . '.php~</comment>');
            else
                $consoleOutput->writeln('<error>Unable to copy the , (' . $filePath . ') file</error>');
        }
    }
}