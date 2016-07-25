<?php

namespace mapdev\cyoa;

use Illuminate\Support\Collection;
use mapdev\FacebookMessenger\Button;
use mapdev\FacebookMessenger\ButtonType;
use mapdev\FacebookMessenger\TemplateElement;

class Story
{
    public $id;
    public $greeting;
    public $image;
    public $start_button;
    public $pages = [];

    public function __construct($id, $greeting, $start_button, Collection $pages, $image = null)
    {
        $this->id = $id;
        $this->greeting = $greeting;
        $this->start_button = $start_button;
        $this->image = $image;
        $this->pages = $pages;
    }

    public function startPage()
    {
        return new Page($this->id, -1, $this->greeting, collect([new Action($this->id, 0, 1, 'Begin!', 1)]), $this->image);
    }

    public function endPage()
    {
        return new Page($this->id, 0, 'The End', collect([new Action($this->id, 0, 0, 'Try Again?', -1)]), $this->image);
    }

    public function carouselItem(){
        //return a  entry for the generric template selector
        return new TemplateElement(substr($this->greeting, 0, 80), null, $this->image, null, [
             new Button(ButtonType::Postback,'Play',null,
                 ['sid' => $this->id, 'pid' => -1, 'aid' => 1, 'np' => 1])
        ]);
    }
}