<?php

namespace Wandi\EasyAdminBundle\Generator;


class Field
{
    private $name;
    private $type;
    private $forcedType;
    private $typeOptions;
    private $label;
    private $help;
    private $basePath;
    private $format;

    /**
     * Field constructor.
     */
    public function __construct()
    {
        $this->typeOptions = ['attr' => []];
        $this->help = '';
        $this->basePath = '';
        $this->forcedType = '';
        $this->format = '';
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
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function buildType()
    {

    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getStructure() : ?array
    {
        $structure = [
            'property' => $this->name,
            'label' => $this->name,
            'type' => $this->forcedType,
            'type_options' => $this->typeOptions,
            'help' => $this->help,
            'format' => $this->format,
            'base_path' => $this->basePath,
        ];

        return self::removeEmptyValuesAndSubArrays($structure);
    }

    /**
     * Supprime Tous les sous tableaux qui sont vides
     * Link: https://stackoverflow.com/a/46781625/7285018
     */
    public static function removeEmptyValuesAndSubArrays($array)
    {
        foreach($array as $k => &$v)
        {
            if (is_array($v))
            {
                $v = self::removeEmptyValuesAndSubArrays($v);
                if (!sizeof($v) )
                    unset($array[$k]);

            } elseif (!strlen($v ) && $v !== false)
                unset($array[$k]);
        }

        return $array;
    }

    /**
     * @return array
     */
    public function getTypeOptions()
    {
        return $this->typeOptions;
    }

    /**
     * @param array $typeOptions
     * @return $this
     */
    public function setTypeOptions(array $typeOptions)
    {
        $this->typeOptions = $typeOptions;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @param mixed $help
     * @return $this
     */
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param mixed $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getForcedType()
    {
        return $this->forcedType;
    }

    /**
     * @param mixed $forcedType
     * @return $this
     */
    public function setForcedType($forcedType)
    {
        $this->forcedType = $forcedType;
        return $this;
    }

    /**
     * @param array $propertyConfig
     * @param Method $method
     */
    public function buildFieldConfig(array $propertyConfig, Method $method)
    {
        $this->name = $propertyConfig['name'];
        $this->type = $propertyConfig['typeConfig']['easyAdminType'];
        $this->label = $propertyConfig['name'];

        //Si le type est forcé et que la methode n'est pas bannie
        if ($propertyConfig['typeConfig']['typeForced'] && (empty($propertyConfig['typeConfig']['methodsTypeForced'])
            || !in_array($method->getName(), $propertyConfig['typeConfig']['methodsTypeForced'])))
            $this->forcedType = $this->type;
    }

    /**
     * @param array $propertyConfig
     * @param Method $method
     */
    private function buildFieldTypeHelpers(array $propertyConfig, Method $method)
    {
        $helpers = ConfigurationTypes::getTypeHelpers();

        foreach ($helpers as $type => $helper)
        {
            $helper = array_replace(ConfigurationTypes::getMaskHelper(), $helper);

            if ($type == $propertyConfig['typeConfig']['easyAdminGeneratorType'] && !in_array($method->getName(), $helper['methods']))
            {
                PropertyTypeHelperFunctions::{$helper['function']}($propertyConfig, $this, $method);
            }
        }
    }

    /**
     * @param array $propertyConfig
     * @param Entity $entity
     * @param method $method
     */
    private function buildFieldClassHelpers(array $propertyConfig, Entity $entity, method $method)
    {
        $helpers = ConfigurationTypes::getClassHelpers();

        foreach ($propertyConfig['annotationClasses'] as $annotation)
        {
            //Si l'entré existe et si la methode est autorisé pour la class spécifié
            //On bannit les méthodes list et show de base
            if (($classHelper = $helpers[get_class($annotation)] ?? null) && (!in_array($method->getName(), ['list', 'show'])
                    || in_array($method->getName(), $classHelper['methods'])) )
            {
                PropertyClassHelperFunctions::{$classHelper['function']}($annotation, $this, $entity, $method);
            }
        }
    }

    /**
     * @param array $propertyConfig
     * @param Entity $entity
     * @param Method $method
     */
    public function buildFieldHelpers(array $propertyConfig, Entity $entity, Method $method)
    {
        //Helpers par rapport aux classes possédés
        $this->buildFieldClassHelpers($propertyConfig, $entity, $method);

        //helpers par rapport au type attribué
        $this->buildFieldTypeHelpers($propertyConfig, $method);
    }

    /**
     * @return mixed
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param mixed $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
}