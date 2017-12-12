<?php

namespace Wandi\EasyAdminBundle\Generator;


class Action
{
    private $name;
    private $icon;
    private $label;

    public function __construct()
    {
        $this->label = '';
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
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
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

    /**
     * @param array $parameters
     * @return string
     */
    public function getIconFromAction(array $parameters) : string
    {
        return $parameters[$this->name] ?? '';
    }

    /**
     * @return array
     */
    public function getStructure() : array
    {
        return ['name' => $this->name, 'label' => $this->label, 'icon' => $this->icon ];
    }

}