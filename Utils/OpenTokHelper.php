<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Utils;

use OpenTok\OpenTok;
use OpenTok\MediaMode;
use OpenTok\ArchiveMode;
use OpenTok\Role;

/**
 * Description of OpenTokHelper
 *
 */
class OpenTokHelper
{
    /**
     * @var OpenTok
     */
    private $client;
    
    private $routedMode;
    
    public function __construct($apiKey, $apiSecret, $routedMode = false)
    {
        $this->client = new OpenTok($apiKey, $apiSecret);
        $this->routedMode = $routedMode;
    }
    
    public function createSession($options = [])
    {
        $defaults = [
            'archiveMode' => ArchiveMode::MANUAL,
            'mediaMode' => $this->routedMode ? MediaMode::ROUTED : MediaMode::RELAYED,
            'p2p.preference' => 'enabled',
            
        ];
        $options = array_merge($defaults, $options);
        
        return $this->client->createSession($options);
    }
    
    public function generateToken($sessionId, $options = [])
    {
        $defaults = [
            'role' => Role::PUBLISHER,
        ];
        $options = array_merge($defaults, $options);
        $token = $this->client->generateToken($sessionId, $options);
        
        return $token;
    }
}
