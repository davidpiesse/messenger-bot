<?php
/**
 * Created by PhpStorm.
 * User: davidpiesse
 * Date: 05/07/2016
 * Time: 22:31
 */

namespace mapdev\cyoa;

use mapdev\FacebookMessenger\Button;
use mapdev\FacebookMessenger\ButtonType;

class Action
{
    public $page_id;
    public $id;
    public $text;
    public $next_page;
    protected $payload;

    public function __construct($story_id, $page_id, $id, $text, $next_page)
    {
        $this->page_id = $page_id;
        $this->id = $id;
        $this->text = $text;
        $this->next_page = $next_page;
        $this->payload = ['sid' => $story_id, 'pid' => $page_id, 'aid' => $this->id, 'np' => $this->next_page];
    }

    public function payload()
    {
        return json_encode($this->payload);
    }

    //build a button object for the page
    public function buildButton(){
        return new Button(ButtonType::Postback,$this->text,null,$this->payload());
    }
}