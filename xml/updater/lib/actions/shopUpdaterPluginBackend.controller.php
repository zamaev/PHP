<?php

class shopUpdaterPluginBackendController extends waController
{
    public function execute()
    {
        shopUpdaterStart::start();
    }
}