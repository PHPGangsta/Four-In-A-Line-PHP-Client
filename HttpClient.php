<?php

class HttpClient
{
    protected $_version = '1.0';

    protected $_playerId;
    protected $_gameId;
    protected $_token;

    public function getAllAvailableEnemies()
    {
        $response = $this->_doCurlRequest('http://fourinaline.phpgangsta.de/game/getavailableenemies', array());

        $enemies = array();
        foreach ($response['enemies'] as $enemy) {
            $enemies[$enemy['enemyId']] = $enemy['name'];
        }

        return $enemies;
    }

    public function createNewGame($playerName, $enemyId, $width, $height)
    {
        $params = array(
            'name'    => $playerName,
            'enemyId' => $enemyId,
            'width'   => $width,
            'height'  => $height
        );

        $response = $this->_doCurlRequest('http://fourinaline.phpgangsta.de/game/create', $params);

        $this->_playerId     = $response['playerId'];
        $this->_gameId       = $response['gameId'];
        $this->_token        = $response['token'];

        return $response['playerNumber'];
    }

    public function getInstruction()
    {
        return $this->_doCurlRequest('http://fourinaline.phpgangsta.de/game/getinstruction', array());
    }

    public function move($column)
    {
        $params = array(
            'column' => $column
        );

        return $this->_doCurlRequest('http://fourinaline.phpgangsta.de/game/move', $params);
    }

    protected function _doCurlRequest($url, $params)
    {
        $additionalParams = array(
            'version' => $this->_version,
            'format'  => 'json'
        );
        if ($this->_playerId !== null) {
            $additionalParams['playerId'] = $this->_playerId;
            $additionalParams['gameId']   = $this->_gameId;
            $additionalParams['token']    = $this->_token;
        }

        $params = array_merge($params, $additionalParams);

        $curl = curl_init();
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_ENCODING , "gzip");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl);
	    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	    curl_close($curl);
	    switch ($status) {
	        case 200:
	            return json_decode($response, true);
	        default:
	            throw new Exception("http error: {$status}", $status);
	    }
    }
}