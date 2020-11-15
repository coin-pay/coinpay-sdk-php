<?php

class CoinPayException extends Exception {
    public function errorMessage()
    {
        return $this->getMessage();
    }
}