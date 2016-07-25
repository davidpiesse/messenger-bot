<?php
/**
 * Created by PhpStorm.
 * User: davidpiesse
 * Date: 05/07/2016
 * Time: 22:30
 */

namespace mapdev\cyoa;


use Illuminate\Support\Collection;
use mapdev\FacebookMessenger\Attachment;
use mapdev\FacebookMessenger\AttachmentItem;
use mapdev\FacebookMessenger\AttachmentMessage;
use mapdev\FacebookMessenger\Button;
use mapdev\FacebookMessenger\ButtonTemplate;
use mapdev\FacebookMessenger\ButtonType;
use mapdev\FacebookMessenger\FileType;
use mapdev\FacebookMessenger\GenericTemplate;
use mapdev\FacebookMessenger\TemplateElement;

class Page
{
    public $story_id;
    public $id;
    public $text;
    public $image;
    public $actions;

    public function __construct($story_id, $id, $text, Collection $actions, $image = null)
    {
        $this->story_id = $story_id;
        $this->id = $id;
        $this->text = $text;
        $this->image = $image;
        $this->actions = $actions;
    }

    public function buildImageMessage()
    {
        return new AttachmentMessage(new AttachmentItem(FileType::Image,$this->image));
//        return new GenericTemplate([new TemplateElement(substr($this->text, 0, 80), null, $this->image, null, [])]);
    }

    //possibly a generic one - to encompass both image and buttons

    public function buildButtonTemplate()
    {
        $buttons = $this->actions->map(function ($item) {
            return $item->buildButton();
        });
        return new ButtonTemplate($this->text, $buttons);
    }
}