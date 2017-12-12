<?php

namespace Wandi\EasyAdminBundle\Generator;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class Entity
{
    private $name;
    private $class;
    private $disabledAction;
    private $methods;
    private $properties;
    private $metaData;

    /**
     * Entity constructor.
     * @param ClassMetadata $metaData
     * @throws \AppBundle\Exception\EAException
     */
    public function __construct(ClassMetadata $metaData)
    {
        $this->methods = new ArrayCollection();
        $this->disabledAction = [];
        $this->metaData = $metaData;
        $this->properties = [];

        $this->initProperties();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return object Entity
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public static function buildName($metaData)
    {
        $entityName = substr($metaData->getName(), strlen($metaData->namespace) + 1);
        $bundleName = substr($metaData->namespace, 0, strripos($metaData->namespace, 'Bundle'));
        return strtolower($bundleName . '_' . $entityName);
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDisabledAction()
    {
        return $this->disabledAction;
    }

    /**
     * @param mixed $disabledAction
     * @return $this
     */
    public function setDisabledAction($disabledAction)
    {
        $this->disabledAction = $disabledAction;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param ArrayCollection $methods
     * @return $this
     */
    public function setMethods(ArrayCollection $methods)
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @param $eaToolParams
     */
    public function buildMethods($eaToolParams): void
    {
        foreach ($eaToolParams['methods'] as $name => $method)
        {
            $method = new Method();
            $method->setName($name);
            $method->buildTitle($this->name);

            foreach ($eaToolParams['methods'][$name] as $actionName)
            {
                $action = new Action();
                $action->setName($actionName);
                $action->setIcon($action->getIconFromAction($eaToolParams['icons']['actions']));
                $action->setLabel($actionName);
                $method->addAction($action);
            }

            foreach ($this->properties as $property)
            {
                //Si le type de la propriété n'est pas accepté pour la method, on next
                if (in_array($name, $property['typeConfig']['methodsNoAllowed']))
                    continue ;

                $field = new Field();
                $field->buildFieldConfig($property, $method);
                $field->buildFieldHelpers($property, $this, $method);
                $method->addField($field);
            }

            $this->addMethod($method);
        }
    }

    /**
     * @param Method $method
     */
    public function addMethod(Method $method)
    {
        $this->methods[] = $method;
    }

    /**
     * @param $eaToolParams
     * @return array
     */
    public function getStructure($eaToolParams): array
    {
        $methodsStructure = [];

        foreach ($this->methods as $method)
            $methodsStructure = array_merge($methodsStructure, $method->getStructure($eaToolParams));

        $structure = [
            'easy_admin' => [
                'entities' => [
                    "$this->name" => array_merge([
                        'class' => $this->class,
                        'disabled_actions' => $this->disabledAction,
                    ], $methodsStructure),
                ]
            ]
        ];

        return $structure;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Configure les propriétés de l'entité
     * @throws \AppBundle\Exception\EAException
     */
    private function initProperties()
    {
        $reflectionProperties = (new \ReflectionClass($this->metaData->getName()))->getProperties();

        foreach ($reflectionProperties as $reflectionProperty)
        {
            $this->properties[] = PropertyConfig::setPropertyConfig($reflectionProperty);
        }

        //Attribution des types par rapport aux types des autres propriétés (VICH,...)
        $this->properties = ConfigurationTypes::setVichPropertiesConfig($this->properties);
    }

    /**
     * @return ClassMetadata
     */
    public function getMetaData(): ClassMetadata
    {
        return $this->metaData;
    }

    /**
     * @param array $metaData
     * @return $this
     */
    public function setMetaData(array $metaData): Entity
    {
        $this->metaData = $metaData;
        return $this;
    }
}