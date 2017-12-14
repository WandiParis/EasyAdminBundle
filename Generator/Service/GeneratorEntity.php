<?php

namespace Wandi\EasyAdminBundle\Generator\Service;

use Wandi\EasyAdminBundle\Generator\EATool;
use Wandi\EasyAdminBundle\Generator\Entity;
use Wandi\EasyAdminBundle\Generator\Exception\EAException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class GeneratorEntity
{
    private $eaToolParams;
    private $em;
    private $projectDir;
    private $container;
    private $consoleOutput;
    private $command;

    /**
     * GeneratorEntity constructor.
     * @param EntityManager $entityManager
     * @param $eaToolParams
     * @param $projectDir
     * @param ContainerInterface $container
     * @param ContainerAwareCommand $entityCommand
     */
    public function __construct(EntityManager $entityManager, $eaToolParams, $projectDir, ContainerInterface $container, ContainerAwareCommand $entityCommand)
    {
        $this->em = $entityManager;
        $this->eaToolParams = $eaToolParams;
        $this->projectDir = $projectDir;
        $this->container = $container;
        $this->command = $entityCommand;
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * TODO: Ajouter la gestion des préfix (AppBundle:Image)
     * TODO: Factoriser les fonctions generateFileEntity avec Eatool class
     * @param $entityFullName
     * @throws EAException
     */
    public function run(string $entityFullName): void
    {
        $entitySplit = explode(':', $entityFullName);
        if (empty($entitySplit) || in_array($entityFullName, $entitySplit) || count($entitySplit) != 2)
        {
            throw new EAException('You have to enter a valid entity name prefixed by the name of the bundle to which it belongs (ex: AppBundle:Image)');
        }
        $bundleName = $entitySplit[0];
        $entityName = $entitySplit[1];
        /** @var ClassMetadata $metaData */
        $entityMetaData = $this->em->getClassMetadata($bundleName . '\Entity\\' . $entityName);
        $relatedEntitiesName = $this->getRelatedEntitiesName($entityMetaData);
        $bundles = $this->container->getParameter('kernel.bundles');

        $eaTool = new EATool();
        $eaTool->setParameters($this->eaToolParams);
        $eaTool->initHelpers();
        $eaTool->setParameterBag($this->container->getParameterBag()->all());
        $eaTool->initTranslation($this->eaToolParams['translation_domain'], $this->projectDir);

        $entity = new Entity($entityMetaData);
        $entity->setName(Entity::buildName($entityMetaData, $bundles));
        $entity->setClass($entityMetaData->getName());
        $entity->buildMethods($this->eaToolParams);
        $eaTool->addEntity($entity);

        //On rajoute les entités liées (les set avant avec les metaData)

        $eaTool->generateEntityFiles($this->projectDir, $this->consoleOutput);

        $this->updateMenuFile($entity);
        $this->updateImportsFile($entity);
    }

    /**
     * @param Entity $entity
     * @throws EAException
     */
    private function updateMenuFile(Entity $entity): void
    {
        $fileMenuContent = Yaml::parse(file_get_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '_menu.yml'));

        if (!isset($fileMenuContent['easy_admin']['design']['menu']))
        {
            throw new EAException('no easy admin menu detected');
        }

        //Si le l'entité n'existe pas dans le menu
        if (false === array_search($entity->getName(), array_column($fileMenuContent['easy_admin']['design']['menu'], 'entity')))
        {
            $fileMenuContent['easy_admin']['design']['menu'][] = EATool::buildEntryMenu($entity->getName());
        }

        $ymlContent = EATool::buildDumpPhpToYml($fileMenuContent, $this->eaToolParams);
        file_put_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '_menu.yml', $ymlContent);
    }

    /**
     * @param Entity $entity
     * @throws EAException
     */
    private function updateImportsFile(Entity $entity): void
    {
        $fileMenuContent = Yaml::parse(file_get_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '.yml'));

        if (!isset($fileMenuContent['imports']))
        {
            throw new EAException('There is no imports option in the configuration file.');
        }

        $patternEntity = $this->eaToolParams['pattern_file'] . '_' . $entity->getName() . '.yml';

        //Si le l'entité n'existe pas dans les fichiers
        if (false === array_search($patternEntity, array_column($fileMenuContent['imports'], 'resource')))
        {
            $fileMenuContent['imports'][] = [
                'resource' => $patternEntity,
            ];
        }

        $ymlContent = EATool::buildDumpPhpToYml($fileMenuContent, $this->eaToolParams);
        if (!file_put_contents($this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '.yml', $ymlContent))
            throw new EAException('Can not update imported files in ' . $this->projectDir . '/app/config/easyadmin/' . $this->eaToolParams['pattern_file'] . '.yml');
    }

    private function getRelatedEntitiesName($entityMetaData): array
    {
        $listMetaData = $this->em->getMetadataFactory()->getAllMetadata();
        $relatedEntities = [];
        $input = new ArgvInput();
        $helper = $this->command->getHelper('question');

        foreach ($listMetaData as $metaData)
        {
            if (empty($metaData->associationMappings))
                continue ;

            foreach ($metaData->associationMappings as $associationMapping)
            {
                if ($associationMapping['targetEntity'] == $entityMetaData->name)
                {
                    //Si déjà présent, on next
                    if (in_array($associationMapping['targetEntity'], $relatedEntities))
                        continue ;
                    $question = new ConfirmationQuestion('L\'entité <info>' . $metaData->name . '</info> est lié, voulez-vous (re)générer son fichier de configuration [<info>y</info>/n]?', true);
                    if ($helper->ask($input, $this->consoleOutput, $question))
                        $relatedEntities[] = $metaData->name;
                }
            }
        }
        return $relatedEntities;
    }
}