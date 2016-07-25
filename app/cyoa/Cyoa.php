<?php
namespace mapdev\cyoa;

//manager class for cyoa
use Illuminate\Http\Request;

class Cyoa
{
    protected $data;
//    public $story;
    public $stories;

    public function __construct()
    {
        $this->data = $this->createData();
        $data = $this->data;
        $this->stories[0] = new Story($data->id, $data->greeting, $data->start_button,
            collect($data->pages)->map(function ($page) use ($data) {
                return new Page($data->id, $page->id, $page->text, collect($page->actions)->map(function ($action) use ($data, $page) {
                    return new Action($data->id, $page->id, $action->id, $action->text, $action->page);
                }), $page->image);
            }), $data->image);
    }

    //change
    public function getPage($json)
    {
        $page = null;
        $payload = Payload::parse($json);
        //check end
        $story = $this->getStory($payload->story_id);
        if ($payload->next_page == -1) {
            $page = $story->startPage();
        } else if ($payload->next_page == 0) {
            $page = $story->endPage();
        } else {
            $page = $story->pages->where('id', $payload->next_page)->first();
            if (is_null($page))
                throw new \Exception("Can't find page");
        }
        return $page;
    }

    protected function getStory($id)
    {
        return collect($this->stories)->where('id', $id)->first();
    }

    //TODO

    //add a specific image text max 80 char

    //load up a second script.

    //determine which story to use by payload sid.

    protected function createData()
    {
        $json = '{
   "id": 1,
   "greeting": "Welcome to \'The Creepy Forest\'",
   "image": "http://www.lovethesepics.com/wp-content/uploads/2012/10/Enchanted-Forest-Doesnt-it-look-Spooky.jpg",
   "start_button": "Let\'s Begin!",
   "pages": [{
      "id": 1,
      "text": "You enter the forest just wandering around. A weird tree is before you",
      "image": "http://images2.wikia.nocookie.net/__cb20120407152942/creepypasta/images/0/0d/Creepy_tree.jpg",
      "actions": [{
         "id": 1,
         "page": 2,
         "text": "Punch the tree"
      }, {
         "id": 2,
         "page": 3,
         "text": "Turn around"
      }]
   }, {
      "id": 2,
      "text": "Nothing happens...",
      "image": "http://images2.wikia.nocookie.net/__cb20120407152942/creepypasta/images/0/0d/Creepy_tree.jpg",
      "actions": [{
         "id": 1,
         "page": 4,
         "text": "Punch the tree again"
      }]
   }, {
      "id": 3,
      "text": "As you turn around a slight hiss gets louder. Finally you see a green monster upon you. It explodes",
      "image": "https://s-media-cache-ak0.pinimg.com/736x/d8/25/0c/d8250c4c6a12c4103d64fcd9643a94e7.jpg",
      "actions": [{
         "id": 1,
         "page": 0,
         "text": "You Died"
      }]
   }, {
      "id": 4,
      "text": "The tree breaks up into logs around your feet. What do you do",
      "image": "http://www.growwhatyoucan.co.uk/othersites/inebg/oak_log_pile2.jpg",
      "actions": [{
         "id": 1,
         "page": 5,
         "text": "Make a wooden sword"
      }, {
         "id": 2,
         "page": 3,
         "text": "Flee"
      }]
   }, {
      "id": 5,
      "text": "You craft a wooden sword and as you stand up from the ground notice a zombie slowly walking towards you grunting \'Errrrr\'",
      "image": "http://cdn.imgs.tuts.dragoart.com/how-to-draw-a-realistic-minecraft-zombie_1_000000018782_5.png",
      "actions": [{
         "id": 1,
         "page": 6,
         "text": "Attack the Zombie"
      }]
   }, {
      "id": 6,
      "text": "You attack; but the Zombie parries your sword and rips off your head",
      "image": "http://2.bp.blogspot.com/-Ihjkc4Rc7vA/T6-MrGRR0kI/AAAAAAAADF8/gB45Wahdxf0/s1600/DSC02817b.jpg",
      "actions": [{
         "id": 1,
         "page": 0,
         "text": "You Died"
      }]
   }]
}';
        return json_decode($json);
    }
}