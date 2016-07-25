<?php
/**
 * Created by PhpStorm.
 * User: davidpiesse
 * Date: 05/07/2016
 * Time: 22:30
 */

namespace mapdev\cyoa;


class Payload
{
    public $story_id;
    public $page_id;
    public $action_id;
    public $next_page;

    public function __construct($story_id, $page_id, $action_id, $next_page)
    {
        $this->story_id = $story_id;
        $this->page_id = $page_id;
        $this->action_id = $action_id;
        $this->next_page = $next_page;
    }

    public function toJson()
    {
        $data = [
            'sid' => $this->story_id,
            'pid' => $this->page_id,
            'aid' => $this->action_id,
            'np' => $this->next_page,
        ];
        return json_encode($data);
    }

    public static function parse($json)
    {
        $data = json_decode($json);
        return new Payload($data->sid, $data->pid, $data->aid, $data->np);
    }
}