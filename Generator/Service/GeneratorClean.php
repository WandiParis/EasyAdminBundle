<?php

namespace Wandi\EasyAdminBundle\Generator\Service;

use Wandi\EasyAdminBundle\Generator\EATool;
use Wandi\EasyAdminBundle\Generator\Entity;
use Wandi\EasyAdminBundle\Generator\Exception\EAException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Yaml;

class GeneratorClean
{
    private $eaToolParams;
    private $em;
    private $projectDir;
    private $consoleOutput;
    private $bundles;

    /**
     * GeneratorClean constructor.
     * @param EntityManager $entityManager
     * @param $eaToolParams
     * @param $projectDir
     * @param $bundles
     */
    public function __construct(EntityManager $entityManager, $eaToolParams, $projectDir, $bundles)
    {
        $this->em = $entityManager;
        $this->eaToolParams = $eaToolParams;
        $this->projectDir = $projectDir;
        $this->consoleOutput = new ConsoleOutput();
        $this->bundles = $bundles;
    }

    /**
     * Suppression des entités dans menu, dans la liste des ficheirs importés et du fichier en lui même
     * TODO: Mettre les methodes de purges dans une autre classe
     *
     * @throws EAException
     */
    public function run(): void
    {
        $fileContent = Yaml::parse(file_get_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '.yml'));
        $metaDataList = $this->em->getMetadataFactory()->getAllMetadata();
        $entitiesName = [
            'metaData' => [],
            'easyAdmin' => [],
        ];

        if (!isset($fileContent['imports']))
            throw new EAException('There are no imported files.');

        $entitiesName['easyAdmin'] = $this->getNameListEntities($fileContent['imports']);
        $entitiesName['metaData'] = $this->getEntitiesNameFromMetaDataList($metaDataList, $this->bundles);
        $entitiesToDelete = $this->getEntitiesToDelete($entitiesName);

        if (empty($entitiesToDelete))
        {
            $this->consoleOutput->writeln('There are no files to clean, cleaning process <info>completed</info>.');
            return ;
        }

        $this->consoleOutput->writeln('<info>Start </info>of cleaning easyadmin configuration files.<br>');
        $this->purgeImportedFiles($entitiesToDelete);
        $this->purgeEasyAdminMenu($entitiesToDelete);
        $this->purgeEntityFiles($entitiesToDelete);
        $this->consoleOutput->writeln('Cleaning process <info>completed</info>');
    }

    /**
     * Recupère le nom des entités à partir des noms des fichiers importés
     * TODO: Récupérer les noms des entités à partir du menu ou d'un tableau généré
     * @param array $files
     * @return array
     */
    private function getNameListEntities(array $files): array
    {
        $entitiesName = [];

        foreach ($files as $fileName)
        {
            if ($fileName['resource'] === $this->eaToolParams['pattern_file'] . '_menu.yml')
                continue ;
            $lengthPattern = strlen($this->eaToolParams['pattern_file']);
            $postPatternFile = strripos($fileName['resource'], $this->eaToolParams['pattern_file'] . '_');
            $entitiesName[] = substr($fileName['resource'], $postPatternFile + $lengthPattern + 1,  - 4 - $postPatternFile );
        }
        return $entitiesName;
    }

    /**
     * Retourne un tableau contenant les noms des entités
     * @param array $metaDataList
     * @param array $bundles
     * @return array
     */
    private function getEntitiesNameFromMetaDataList(array $metaDataList, array $bundles): array
    {
        $entitiesName = array_map(function($metaData) use ($bundles){
            $nameData = Entity::buildNameData($metaData, $bundles);
            return Entity::buildName($nameData);
        }, $metaDataList);
        return $entitiesName;
    }

    /**
     * @param $entities
     * @return array
     * Retourne la liste des entités à supprimer
     */
    private function getEntitiesToDelete(array $entities): array
    {
        $entitiesToDelete = [];

        foreach (array_diff($entities['easyAdmin'], $entities['metaData']) as $entity)
        {
            $entitiesToDelete['name'][] = $entity;
            $entitiesToDelete['pattern'][] = $this->eaToolParams['pattern_file'] . '_' . $entity . '.yml';
        }

        return $entitiesToDelete;
    }

    /**
     * @param $entities
     * @throws EAException
     */
    private function purgeImportedFiles(array $entities): void
    {
        $fileBaseContent = Yaml::parse(file_get_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '.yml'));

        if (!isset($fileBaseContent['imports']))
        {
            throw new EAException('No imported files2');
        }

        foreach ($fileBaseContent['imports'] as $key => $import)
        {
            if (in_array($import['resource'], $entities['pattern']))
                unset($fileBaseContent['imports'][$key]);
        }

        $fileBaseContent['imports'] = array_values($fileBaseContent['imports']);
        $ymlContent = EATool::buildDumpPhpToYml($fileBaseContent, $this->eaToolParams);
        file_put_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '.yml', $ymlContent);
    }

    /**
     * @param $entities
     * @throws EAException
     */
    private function purgeEasyAdminMenu(array $entities): void
    {
        $fileMenuContent = Yaml::parse(file_get_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '_menu.yml'));

        if (!isset($fileMenuContent['easy_admin']['design']['menu']))
        {
            throw new EAException('no easy admin menu detected');
        }

        foreach ($fileMenuContent['easy_admin']['design']['menu'] as $key => $entry)
        {
            if (in_array($entry['entity'], $entities['name']))
                unset($fileMenuContent['easy_admin']['design']['menu'][$key]);
        }

        $fileMenuContent['easy_admin']['design']['menu'] = array_values($fileMenuContent['easy_admin']['design']['menu']);
        $ymlContent = EATool::buildDumpPhpToYml($fileMenuContent, $this->eaToolParams);
        file_put_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '_menu.yml', $ymlContent);
    }

    /**
     * @param $entities
     * @throws EAException
     */
    private function purgeEntityFiles(array $entities): void
    {
        foreach ($entities['name'] as $entityName)
        {
            $this->consoleOutput->writeln('Purging entity <info>' . $entityName . '</info>');
            $path = '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '_' . $entityName . '.yml';
            if (unlink($this->projectDir . $path))
                $this->consoleOutput->writeln('   >File <comment>' . $path . ' </comment> has been <info>deleted</info>.');
            else
                throw new EAException('Unable to delete configuration file for ' . $entityName . ' entity');
        }
    }
}
