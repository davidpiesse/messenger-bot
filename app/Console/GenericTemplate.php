<?php
namespace App\Console;


use App\TemplateInterface;

class GenericTemplate implements TemplateInterface
{

    protected $type = 'template';
    protected $template_type = 'generic';
    protected $elements = [];

    public function __construct($elements)
    {
        $this->elements = $elements;
    }

    public function toData()
    {
        // TODO: Implement toData() method.
    }
}