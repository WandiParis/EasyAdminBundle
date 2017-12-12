<?php

namespace Wandi\EasyAdminBundle\Generator\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GeneratorGenerate
{
    private $em;
    private $projectDir;
    private $eaToolParams;
    private $vichMappings;
    private $container;
    private $consoleOutput;

    /**
     * GeneratorGenerate constructor.
     * @param EntityManager $entityManager
     * @param $eaToolParams
     * @param $projectDir
     * @param $vichMappings
     * @param ContainerInterface $container
     */
    public function __construct(EntityManager $entityManager, $eaToolParams, $projectDir, $vichMappings, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->projectDir = $projectDir;
        $this->eaToolParams = $eaToolParams;
        $this->vichMappings = $vichMappings;
        $this->container = $container;
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * Génère les fichiers d'entités, le fichier menu et le fichier de base
     * @throws \AppBundle\Exception\EAException
     */
    public function run(): void
    {
        $listMetaData = $this->em->getMetadataFactory()->getAllMetadata();

        $eaTool = new EATool();
        $eaTool->setParameters($this->eaToolParams);
        $eaTool->initHelpers();
        $eaTool->setParameterBag($this->container->getParameterBag()->all());
        $eaTool->initTranslation($this->eaToolParams['translation_domain'], $this->projectDir);

        if (empty($listMetaData))
        {
            $this->consoleOutput->writeln('<comment>There are no entities to configure, the generation process is stopped.</comment>');
            return ;
        }

        foreach ($listMetaData as $metaData)
        {
            /** @var ClassMetadata $metaData */
            $entity = new Entity($metaData);
            $entity->setName(Entity::buildName($metaData));
            $entity->setClass($metaData->getName());
            $entity->buildMethods($eaTool->getParameters());

            $eaTool->addEntity($entity);
        }

        $eaTool->generateMenuFile($this->projectDir, $this->consoleOutput);
        $eaTool->generateEntityFiles($this->projectDir, $this->consoleOutput);
        $eaTool->generateBaseFile($this->projectDir, $this->consoleOutput);
    }
}