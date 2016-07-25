<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use mapdev\cyoa\Action;
use mapdev\cyoa\Cyoa;
use mapdev\cyoa\Page;
use mapdev\cyoa\Story;

use mapdev\FacebookMessenger\Attachment;
use mapdev\FacebookMessenger\AttachmentItem;
use mapdev\FacebookMessenger\AttachmentMessage;
use mapdev\FacebookMessenger\Button;
use mapdev\FacebookMessenger\ButtonTemplate;
use mapdev\FacebookMessenger\ButtonType;
use mapdev\FacebookMessenger\ButtonTypes;
use mapdev\FacebookMessenger\Callback;
use mapdev\FacebookMessenger\CallbackInterface;
use mapdev\FacebookMessenger\FileType;
use mapdev\FacebookMessenger\FileTypes;
use mapdev\FacebookMessenger\GenericTemplate;
use mapdev\FacebookMessenger\MenuItem;
use mapdev\FacebookMessenger\MessageDelivered;
use mapdev\FacebookMessenger\MessageReceived;
use mapdev\FacebookMessenger\Messenger;
use mapdev\FacebookMessenger\MessengerApi;
use mapdev\FacebookMessenger\PostbackReceived;
use mapdev\FacebookMessenger\QuickReply;
use mapdev\FacebookMessenger\Reply;
use mapdev\FacebookMessenger\SenderActions;
use mapdev\FacebookMessenger\TemplateElement;
use mapdev\FacebookMessenger\TextMessage;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Log;
use mapdev\FacebookMessenger\ThreadSettingType;

class WebhookController extends Controller
{
    public function get(Request $request)
    {
        $messenger = new Messenger(env('PAGE_ACCESS_TOKEN'));
        return $messenger->hubReply($request);
    }

    public function post(Request $request)
    {
//        return response('', 200);
        try {
            $cyoa = new Cyoa();
            $messenger = new Messenger(env('PAGE_ACCESS_TOKEN'));

            $data = $request->all();
            Log::info('Incoming Data', ['data' => $data]);
            $callback = new Callback($data);

            //Postback Check
            $callback->postbackMessages()->each(function ($entryMessage) use ($cyoa, $messenger) {
                $postback = $entryMessage->postback;

                if ($entryMessage->sender_id == env('FB_PAGE_ID')) {
                    Log::info('Bot Sender', ['data' => $entryMessage]);
                    return response('', 200);
                }

                switch ($postback->payload) {
                    case 'audio':
                        $messenger->sendMessage(new AttachmentMessage(new AttachmentItem(FileType::Audio, url('files/taylor.mp3'))), $entryMessage->sender_id);
                        break;
                    case 'text':
                        $messenger->sendMessage(new TextMessage("You pressed Postback"), $entryMessage->sender_id);
                        break;
                    default:
                        $page = $cyoa->getPage($postback->payload);
                        $messenger->sendMessage($page->buildImageMessage(), $entryMessage->sender_id);
                        $messenger->sendTemplate($page->buildButtonTemplate(), $entryMessage->sender_id);
                        break;
                }
            });

            //Message Check
            $callback->textMessages()->each(function ($entryMessage) use ($cyoa, $messenger) {
                $message = $entryMessage->message;
                if ($entryMessage->sender_id == env('FB_PAGE_ID')) {
                    Log::info('Bot Sender', ['data' => $entryMessage]);
                    return response('', 200);
                }

                switch (trim(strtolower($message->text))) {
                    case 'start':
                        $page = $cyoa->stories[0]->startPage();
                        $messenger->sendMessage($page->buildImageMessage(), $entryMessage->sender_id);
                        $messenger->sendTemplate($page->buildButtonTemplate(), $entryMessage->sender_id);
                        break;
                    case 'help':
                        $messenger->sendMessage(new TextMessage("Type one of the following words; start, text, image, audio, video, file, quick, generic, buttons, or typing"), $entryMessage->sender_id);
                        break;
                    case 'text':
                        $messenger->sendMessage(new TextMessage("This is a basic text message with a maximum length of 320 characters"), $entryMessage->sender_id);
                        break;
                    case 'image':
                        $messenger->sendMessage(new AttachmentMessage(
                            new AttachmentItem(FileType::Image, url('files/image.jpg'))), $entryMessage->sender_id);
                        break;
                    case 'audio':
                        $messenger->sendMessage(new TextMessage("A bit of T-Swizzle for you"), $entryMessage->sender_id);
                        $messenger->sendMessage(new AttachmentMessage(new AttachmentItem(FileType::Audio, url('files/taylor.mp3'))), $entryMessage->sender_id);
                        break;
                    case 'video':
                        //repeats
                        $messenger->sendMessage(new AttachmentMessage(new AttachmentItem(FileType::Video, url('files/sample.mp4'))), $entryMessage->sender_id);
                        break;
                    case 'file':
                        $messenger->sendMessage(new AttachmentMessage(new AttachmentItem(FileType::File, url('files/sample.pdf'))), $entryMessage->sender_id);
                        break;
                    case 'quick':
                        $messenger->sendMessage(
                            new QuickReply('Choose one of the two quick replies', [
                                new Reply('text', 'Taylor Swift', 'TS'),
                                new Reply('text', 'Katy Perry', 'KP')
                            ], null)
                            , $entryMessage->sender_id);
                        break;
                    case 'generic':
                        $messenger->sendTemplate(
                            new GenericTemplate([
                                new TemplateElement('1989', 'https://www.amazon.co.uk/1989-Taylor-Swift/dp/B00MV0FT4Y', url('files/1989.jpg'), 'by Taylor Swift', [
                                    new Button(ButtonType::Postback, 'Listen', null, 'audio')
                                ]),
                                new TemplateElement('Red', 'https://www.amazon.co.uk/Red-Taylor-Swift/dp/B0097RFB42', url('files/red.jpg'), 'by Taylor Swift', []),
                            ])
                            , $entryMessage->sender_id);
                        break;
                    case 'buttons':
                        $messenger->sendTemplate(
                            new ButtonTemplate('Example Button Template', [
                                    //buttons
                                    new Button(ButtonType::Web, 'A Web Link', 'https://www.google.com'),
                                    new Button(ButtonType::Postback, 'A Postback', null, 'text'),
                                ]
                            ), $entryMessage->sender_id);
                        break;
                    case 'typing':
                        $messenger->sendAction(SenderActions::TypingOn, $message->sender_id);
                        break;
                    case 'taylor swift':
                        $messenger->sendMessage(new TextMessage("We love Taylor too!"), $entryMessage->sender_id);
                        break;
                    case 'katy perry':
                        $messenger->sendMessage(new TextMessage("We love Katy too!"), $entryMessage->sender_id);
                        break;
                    default:
                        Log::info(json_encode($message));
                        $messenger->sendMessage(new TextMessage("Sorry, I didn't understand that. type help for a list of commands"), $entryMessage->sender_id);
                        break;
                }
            });


//            collect($callback->messages)->each(function ($item) use ($cyoa, $messenger) {
//                if ($item->sender_id == env('FB_PAGE_ID')) {
//                    Log::info('Bot Sender', ['data' => $item]);
//                    return response('', 200);
//                }
//                if (get_class($item) == MessageReceived::class) {
//                    if (isset($item->is_echo)) {
//                        Log::info('Echo Data', ['item' => $item]);
//                        return response('', 200);
//                    }
//                    switch (trim(strtolower($item->text))) {
//                        case 'start':
//                            $page = $cyoa->stories[0]->startPage();
//                            $messenger->sendMessage($page->buildImageMessage(), $item->sender_id);
//                            $messenger->sendTemplate($page->buildButtonTemplate(), $item->sender_id);
//                            break;
//                        case 'help':
//                            $messenger->sendMessage(new TextMessage("Type one of the following words; start, text, image, audio, video, file, quick, generic, buttons, or typing"), $item->sender_id);
//                            break;
//                        case 'text':
//                            $messenger->sendMessage(new TextMessage("This is a basic text message with a maximum length of 320 characters"), $item->sender_id);
//                            break;
//                        case 'image':
//                            $messenger->sendMessage(new AttachmentMessage(
//                                new Attachment(FileType::Image, url('files/image.jpg'))), $item->sender_id);
//                            break;
//                        case 'audio':
//                            $messenger->sendMessage(new TextMessage("A bit of T-Swizzle for you"), $item->sender_id);
//                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::Audio, url('files/taylor.mp3'))), $item->sender_id);
//                            break;
//                        case 'video':
//                            //repeats
//                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::Video, url('files/sample.mp4'))), $item->sender_id);
//                            break;
//                        case 'file':
////                            $file = Storage::get('test.txt');
////                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::File, $file->getRealPath())), $item->sender_id);
//                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::File, url('files/sample.pdf'))), $item->sender_id);
//                            break;
//                        case 'quick':
//                            $messenger->sendMessage(
//                                new QuickReply('Choose one of the two quick replies', [
//                                    new Reply('text', 'Taylor Swift', 'TS'),
//                                    new Reply('text', 'Katy Perry', 'KP')
//                                ], null)
//                                , $item->sender_id);
//                            break;
//                        case 'generic':
//                            $messenger->sendTemplate(
//                                new GenericTemplate([
//                                    new TemplateElement('1989', 'https://www.amazon.co.uk/1989-Taylor-Swift/dp/B00MV0FT4Y', url('files/1989.jpg'), 'by Taylor Swift', [
//                                        new Button(ButtonType::Postback, 'Listen', null, 'audio')
//                                    ]),
//                                    new TemplateElement('Red', 'https://www.amazon.co.uk/Red-Taylor-Swift/dp/B0097RFB42', url('files/red.jpg'), 'by Taylor Swift', []),
//                                ])
//                                , $item->sender_id);
//                            break;
//                        case 'buttons':
//                            $messenger->sendTemplate(
//                                new ButtonTemplate('Example Button Template', [
//                                        //buttons
//                                        new Button(ButtonType::Web, 'A Web Link', 'https://www.google.com'),
//                                        new Button(ButtonType::Postback, 'A Postback', null, 'text'),
//                                    ]
//                                ), $item->sender_id);
//                            break;
//                        case 'typing':
//                            $messenger->sendAction(SenderActions::TypingOn, $item->sender_id);
//                            break;
//                        case 'taylor swift':
//                            $messenger->sendMessage(new TextMessage("We love Taylor too!"), $item->sender_id);
//                            break;
//                        case 'katy perry':
//                            $messenger->sendMessage(new TextMessage("We love Katy too!"), $item->sender_id);
//                            break;
//                        default:
//                            Log::info(json_encode($item));
//                            $messenger->sendMessage(new TextMessage("Sorry, I didn't understand that. type help for a list of commands"), $item->sender_id);
//                            break;
//                    }
//                } else if (get_class($item) == PostbackReceived::class) {
//                    if ($item->payload == 'audio')
//                        $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::Audio, url('files/taylor.mp3'))), $item->sender_id);
//                    else if ($item->payload == 'text')
//                        $messenger->sendMessage(new TextMessage("You pressed Postback"), $item->sender_id);
//                    $page = $cyoa->getPage($item->payload);
//                    $messenger->sendMessage($page->buildImageMessage(), $item->sender_id);
//                    $messenger->sendTemplate($page->buildButtonTemplate(), $item->sender_id);
//                }
//            });
//            collect($callback->messages)->each(function ($item) use ($cyoa, $messenger) {
//                if ($item->sender_id == env('FB_PAGE_ID')) {
//                    Log::info('Bot Sender', ['data' => $item]);
//                    return response('', 200);
//                }
//                if (get_class($item) == MessageReceived::class) {
//                    if (isset($item->is_echo)) {
//                        Log::info('Echo Data', ['item' => $item]);
//                        return response('', 200);
//                    }
//                    switch (trim(strtolower($item->text))) {
//                        case 'start':
//                            $page = $cyoa->stories[0]->startPage();
//                            $messenger->sendMessage($page->buildImageMessage(), $item->sender_id);
//                            $messenger->sendTemplate($page->buildButtonTemplate(), $item->sender_id);
//                            break;
//                        case 'help':
//                            $messenger->sendMessage(new TextMessage("Type one of the following words; start, text, image, audio, video, file, quick, generic, buttons, or typing"), $item->sender_id);
//                            break;
//                        case 'text':
//                            $messenger->sendMessage(new TextMessage("This is a basic text message with a maximum length of 320 characters"), $item->sender_id);
//                            break;
//                        case 'image':
//                            $messenger->sendMessage(new AttachmentMessage(
//                                new Attachment(FileType::Image, url('files/image.jpg'))), $item->sender_id);
//                            break;
//                        case 'audio':
//                            $messenger->sendMessage(new TextMessage("A bit of T-Swizzle for you"), $item->sender_id);
//                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::Audio, url('files/taylor.mp3'))), $item->sender_id);
//                            break;
//                        case 'video':
//                            //repeats
//                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::Video, url('files/sample.mp4'))), $item->sender_id);
//                            break;
//                        case 'file':
////                            $file = Storage::get('test.txt');
////                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::File, $file->getRealPath())), $item->sender_id);
//                            $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::File, url('files/sample.pdf'))), $item->sender_id);
//                            break;
//                        case 'quick':
//                            $messenger->sendMessage(
//                                new QuickReply('Choose one of the two quick replies', [
//                                    new Reply('text', 'Taylor Swift', 'TS'),
//                                    new Reply('text', 'Katy Perry', 'KP')
//                                ], null)
//                                , $item->sender_id);
//                            break;
//                        case 'generic':
//                            $messenger->sendTemplate(
//                                new GenericTemplate([
//                                    new TemplateElement('1989', 'https://www.amazon.co.uk/1989-Taylor-Swift/dp/B00MV0FT4Y', url('files/1989.jpg'), 'by Taylor Swift', [
//                                        new Button(ButtonType::Postback, 'Listen', null, 'audio')
//                                    ]),
//                                    new TemplateElement('Red', 'https://www.amazon.co.uk/Red-Taylor-Swift/dp/B0097RFB42', url('files/red.jpg'), 'by Taylor Swift', []),
//                                ])
//                                , $item->sender_id);
//                            break;
//                        case 'buttons':
//                            $messenger->sendTemplate(
//                                new ButtonTemplate('Example Button Template', [
//                                        //buttons
//                                        new Button(ButtonType::Web, 'A Web Link', 'https://www.google.com'),
//                                        new Button(ButtonType::Postback, 'A Postback', null, 'text'),
//                                    ]
//                                ), $item->sender_id);
//                            break;
//                        case 'typing':
//                            $messenger->sendAction(SenderActions::TypingOn, $item->sender_id);
//                            break;
//                        case 'taylor swift':
//                            $messenger->sendMessage(new TextMessage("We love Taylor too!"), $item->sender_id);
//                            break;
//                        case 'katy perry':
//                            $messenger->sendMessage(new TextMessage("We love Katy too!"), $item->sender_id);
//                            break;
//                        default:
//                            Log::info(json_encode($item));
//                            $messenger->sendMessage(new TextMessage("Sorry, I didn't understand that. type help for a list of commands"), $item->sender_id);
//                            break;
//                    }
//                } else if (get_class($item) == PostbackReceived::class) {
//                    if ($item->payload == 'audio')
//                        $messenger->sendMessage(new AttachmentMessage(new Attachment(FileType::Audio, url('files/taylor.mp3'))), $item->sender_id);
//                    else if ($item->payload == 'text')
//                        $messenger->sendMessage(new TextMessage("You pressed Postback"), $item->sender_id);
//                    $page = $cyoa->getPage($item->payload);
//                    $messenger->sendMessage($page->buildImageMessage(), $item->sender_id);
//                    $messenger->sendTemplate($page->buildButtonTemplate(), $item->sender_id);
//                }
//            });
            return response('', 200);
        } catch (\Exception $ex) {
            Log::warning('Error in Webhook', ['ex' => $ex]);
            return response('', 200);
        }
    }


}
