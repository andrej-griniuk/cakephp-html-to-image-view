<?php
use Cake\Event\Event;
use Cake\Event\EventManager;

EventManager::instance()->on(
    'Controller.initialize',
    function (Event $event) {
        $controller = $event->getSubject();
        if ($controller->components()->has('RequestHandler')) {
            $controller->RequestHandler->setConfig('viewClassMap.jpg', 'HtmlToImageView.HtmlToImage');
            $controller->RequestHandler->setConfig('viewClassMap.png', 'HtmlToImageView.HtmlToImage');
        }
    }
);
